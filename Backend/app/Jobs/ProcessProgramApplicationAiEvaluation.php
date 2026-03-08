<?php

namespace App\Jobs;

use App\Models\ProgramApplication;
use App\Models\FormAiScoringConfig;
use App\Services\AiEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProgramApplicationAiEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum retry attempts for transient AI issues.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $applicationId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(AiEvaluationService $aiEvaluationService): void
    {
        $application = ProgramApplication::find($this->applicationId);

        if (!$application || $application->isArchived() || $application->type !== 'submission') {
            return;
        }

        $config = FormAiScoringConfig::where('form_id', $application->form_id)->first();

        // Skip when AI config is missing or disabled
        if (!$config || blank($config->ai_prompt)) {
            $this->markAsSkipped($application, 'AI evaluation is not configured for this form.');
            return;
        }

        $criteria = $config->activeAssessmentCriteria()->with('formFields')->get();
        if ($criteria->isEmpty()) {
            $this->markAsSkipped($application, 'AI evaluation skipped: no active assessment criteria.');
            return;
        }

        $answers = $application->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes
            ? $application->form_submissions->toArray()
            : (array) $application->form_submissions;

        // Ensure we have answers before calling AI
        if (empty($answers)) {
            $this->markAsSkipped($application, 'AI evaluation skipped: no answers found for this submission.');
            return;
        }

        $result = $aiEvaluationService->evaluate(
            $application->form_id,
            $answers,
            $application->id,
            'program_application'
        );

        if (!$result['success']) {
            $this->markAsPending($application, $result['message'] ?? 'AI evaluation is delayed and will be retried.');
            return;
        }

        $response = $result['response'] ?? [];

        $application->applyAiEvaluationResponse(
            $response,
            'completed',
            $response['message'] ?? null,
            $config,
            $criteria
        );
    }

    /**
     * Mark AI evaluation as skipped (no config / criteria / answers).
     */
    protected function markAsSkipped(ProgramApplication $application, string $message): void
    {
        Log::warning('AI evaluation skipped for program application', [
            'application_id' => $application->id,
            'form_id' => $application->form_id,
            'reason' => $message,
        ]);

        $application->applyAiEvaluationResponse(
            ['message' => $message],
            'skipped'
        );
    }

    /**
     * Mark AI evaluation as pending / delayed due to API issues.
     */
    protected function markAsPending(ProgramApplication $application, string $message): void
    {
        Log::error('AI evaluation failed for program application', [
            'application_id' => $application->id,
            'form_id' => $application->form_id,
            'message' => $message,
        ]);

        $application->applyAiEvaluationResponse(
            ['message' => $message],
            'pending'
        );
    }
}

