<?php

namespace App\Services;

use App\Models\ProgramApplication;
use App\Models\RegistrationEvaluation;
use App\Models\RegistrationEvaluationCriterion;
use App\Models\RegistrationEvaluationForm;
use App\Models\RegistrationEvaluator;
use App\Models\RegistrationFormConfig;
use Illuminate\Support\Collection;

class RegistrationEvaluationService
{
    /**
     * Calculate the final evaluation score for an application.
     * Sums all evaluator scores across all assigned criteria, applying weights.
     * IN-2053: Calculate Final Registration Score
     */
    public function calculateFinalScore(int $applicationId): float
    {
        $evaluations = RegistrationEvaluation::where('program_application_id', $applicationId)->get();

        if ($evaluations->isEmpty()) {
            return 0;
        }

        $totalWeightedScore = 0;
        $totalWeight = 0;

        // Group evaluations by criterion to apply weights
        $byCriterion = $evaluations->groupBy('registration_evaluation_criterion_id');

        foreach ($byCriterion as $criterionId => $criterionEvaluations) {
            $criterion = RegistrationEvaluationCriterion::find($criterionId);
            if (!$criterion) continue;

            // Average score from all evaluators for this criterion
            $avgScore = $criterionEvaluations->avg('score');
            $weight = $criterion->weight ?? 100;
            $maxScore = $criterion->max_score;

            // Normalize score to percentage, then apply weight
            $normalizedScore = $maxScore > 0 ? ($avgScore / $maxScore) * 100 : 0;
            $totalWeightedScore += $normalizedScore * ($weight / 100);
            $totalWeight += $weight;
        }

        // Return weighted average as a percentage
        return $totalWeight > 0 ? round($totalWeightedScore / ($totalWeight / 100), 2) : 0;
    }

    /**
     * Update the application's final evaluation score.
     */
    public function updateApplicationScore(int $applicationId): float
    {
        $score = $this->calculateFinalScore($applicationId);

        $application = ProgramApplication::find($applicationId);
        if ($application) {
            $application->update(['final_evaluation_score' => $score]);
        }

        return $score;
    }

    /**
     * Check if application meets the minimum score threshold.
     * IN-2055: Minimum Score Threshold
     */
    public function meetsMinimumThreshold(int $applicationId): bool
    {
        $application = ProgramApplication::find($applicationId);
        if (!$application) return false;

        $threshold = $this->getMinimumThreshold($application->program_id);
        if ($threshold === null) return true; // No threshold set

        $score = $application->final_evaluation_score ?? $this->calculateFinalScore($applicationId);

        return $score >= $threshold;
    }

    /**
     * Get the minimum score threshold for a program.
     */
    public function getMinimumThreshold(int $programId): ?float
    {
        // Check program_applications table first
        $config = RegistrationFormConfig::where('program_id', $programId)->first();
        if ($config && $config->minimum_score_threshold !== null) {
            return (float) $config->minimum_score_threshold;
        }

        return null;
    }

    /**
     * Get the evaluation score breakdown for an application.
     * IN-2057: View Evaluation Score Breakdown
     */
    public function getScoreBreakdown(int $applicationId): array
    {
        $application = ProgramApplication::find($applicationId);
        if (!$application) return [];

        $programId = $application->program_id;
        $forms = RegistrationEvaluationForm::where('program_id', $programId)
            ->where('status', 'published')
            ->with('criteria')
            ->orderBy('sort_order')
            ->get();

        $evaluators = RegistrationEvaluator::where('program_id', $programId)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $evaluations = RegistrationEvaluation::where('program_application_id', $applicationId)
            ->get()
            ->groupBy(['registration_evaluator_id', 'registration_evaluation_criterion_id']);

        $breakdown = [];

        foreach ($forms as $form) {
            $formData = [
                'form_id' => $form->id,
                'form_name' => $form->getTranslation('name', 'en'),
                'form_name_ar' => $form->getTranslation('name', 'ar'),
                'dimension' => $form->dimension,
                'max_possible_score' => $form->getMaxPossibleScore(),
                'criteria' => [],
            ];

            foreach ($form->criteria as $criterion) {
                $criterionData = [
                    'criterion_id' => $criterion->id,
                    'name' => $criterion->getTranslation('name', 'en'),
                    'name_ar' => $criterion->getTranslation('name', 'ar'),
                    'max_score' => $criterion->max_score,
                    'weight' => $criterion->weight,
                    'evaluator_scores' => [],
                    'average_score' => 0,
                ];

                $scores = [];
                foreach ($evaluators as $evaluator) {
                    $evaluation = $evaluations[$evaluator->id][$criterion->id][0] ?? null;
                    $score = $evaluation ? $evaluation->score : null;
                    $comment = $evaluation ? $evaluation->comment : null;

                    $criterionData['evaluator_scores'][] = [
                        'evaluator_id' => $evaluator->id,
                        'evaluator_name' => $evaluator->user?->name ?? 'Unknown',
                        'score' => $score,
                        'comment' => $comment,
                    ];

                    if ($score !== null) {
                        $scores[] = $score;
                    }
                }

                $criterionData['average_score'] = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : 0;
                $formData['criteria'][] = $criterionData;
            }

            $breakdown[] = $formData;
        }

        // Calculate overall totals
        $totalScore = $this->calculateFinalScore($applicationId);
        $threshold = $this->getMinimumThreshold($programId);

        return [
            'application_id' => $applicationId,
            'final_score' => $totalScore,
            'minimum_threshold' => $threshold,
            'meets_threshold' => $threshold !== null ? $totalScore >= $threshold : null,
            'evaluator_count' => $evaluators->count(),
            'forms' => $breakdown,
        ];
    }

    /**
     * Get all evaluators who have completed evaluation for an application.
     */
    public function getCompletedEvaluators(int $applicationId, int $programId): Collection
    {
        return RegistrationEvaluator::where('program_id', $programId)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($evaluator) => $evaluator->hasCompletedEvaluation($applicationId));
    }

    /**
     * Check if all evaluators have completed their evaluation for an application.
     */
    public function isFullyEvaluated(int $applicationId, int $programId): bool
    {
        $evaluators = RegistrationEvaluator::where('program_id', $programId)
            ->where('is_active', true)
            ->get();

        if ($evaluators->isEmpty()) return false;

        return $evaluators->every(fn ($evaluator) => $evaluator->hasCompletedEvaluation($applicationId));
    }
}
