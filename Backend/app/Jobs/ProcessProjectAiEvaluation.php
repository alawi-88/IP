<?php

namespace App\Jobs;

use App\Models\FormAiScoringConfig;
use App\Models\Project;
use App\Services\AiEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class ProcessProjectAiEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum retry attempts for transient AI issues.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $projectId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(AiEvaluationService $aiEvaluationService): void
    {
        $project = Project::find($this->projectId);

        // Ensure project exists, is active, and is a submission
        if (!$project || $project->isArchived() || $project->type !== 'submission') {
            return;
        }

        $config = FormAiScoringConfig::where('form_id', $project->form_id)->first();

        // Skip when AI config is missing or disabled
        if (!$config || blank($config->ai_prompt)) {
            $this->markAsSkipped($project, 'AI evaluation is not configured for this form.');
            return;
        }

        $criteria = $config->activeAssessmentCriteria()->with('formFields')->get();
        if ($criteria->isEmpty()) {
            $this->markAsSkipped($project, 'AI evaluation skipped: no active assessment criteria.');
            return;
        }

        $submissions = $project->form_submissions;
        $answers = $submissions instanceof SchemalessAttributes
            ? $submissions->toArray()
            : (array) $submissions;

        // Ensure we have answers before calling AI
        if (empty($answers)) {
            $this->markAsSkipped($project, 'AI evaluation skipped: no answers found for this project submission.');
            return;
        }

        $result = $aiEvaluationService->evaluate(
            $project->form_id,
            $answers,
            $project->id,
            'project'
        );

        if (!$result['success']) {
            $this->markAsPending($project, $result['message'] ?? 'AI evaluation is delayed and will be retried.');
            return;
        }

        $response = $result['response'] ?? [];

        // Calculate AI scores similar to ProgramApplication::summarizeAiEvaluation
        $criteriaData = collect(data_get($response, 'data.criteria', []));

        $totalScore = $criteriaData->sum(fn($criterion) => (float) data_get($criterion, 'totalScore', 0));
        $maxWeight = $criteriaData->sum(fn($criterion) => (float) data_get($criterion, 'maxWeight', 0));

        $targetTotalWeight = $config->total_weight ?? $maxWeight;

        $normalizedScore = $maxWeight > 0
            ? round(($totalScore / $maxWeight) * $targetTotalWeight, 2)
            : null;

        $payload = $response;
        $payload['status'] = 'completed';
        $payload['meta'] = [
            'total_score' => $totalScore,
            'max_weight' => $maxWeight,
            'target_total_weight' => $targetTotalWeight,
            'normalized_score' => $normalizedScore,
        ];

        // Only update total_score with AI score if there are no judge evaluations
        // Judge evaluations take precedence and are stored in total_score via updateScore()
        $updateData = [
            'ai_evaluation_response' => $payload,
            'ai_evaluated_at' => now(),
        ];
        
        // Check if project has judge evaluations (FormEvaluationScore records)
        $hasJudgeEvaluations = \App\Models\FormEvaluationScore::whereHas('judgeProject', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })
        ->where('is_archived', false)
        ->where('evaluation_score', '>', 0)
        ->exists();
        
        // Only set total_score to AI score if no judge evaluations exist
        // Judge evaluations should take precedence
        if (!$hasJudgeEvaluations) {
            $updateData['total_score'] = $normalizedScore ?? $totalScore;
        }
        
        $project->update($updateData);
    }

    /**
     * Mark AI evaluation as skipped (no config / criteria / answers).
     */
    protected function markAsSkipped(Project $project, string $message): void
    {
        Log::warning('AI evaluation skipped for project', [
            'project_id' => $project->id,
            'form_id' => $project->form_id,
            'reason' => $message,
        ]);

        $project->update([
            'ai_evaluation_response' => [
                'status' => 'skipped',
                'message' => $message,
            ],
            'ai_evaluated_at' => null,
        ]);
    }

    /**
     * Mark AI evaluation as pending / delayed due to API issues.
     */
    protected function markAsPending(Project $project, string $message): void
    {
        Log::error('AI evaluation failed for project', [
            'project_id' => $project->id,
            'form_id' => $project->form_id,
            'message' => $message,
        ]);

        $project->update([
            'ai_evaluation_response' => [
                'status' => 'pending',
                'message' => $message,
            ],
            'ai_evaluated_at' => null,
        ]);
    }
}

