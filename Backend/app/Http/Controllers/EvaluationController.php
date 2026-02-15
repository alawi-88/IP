<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListEvaluationsRequest;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\DisclaimerAcceptance;
use App\Models\Form;
use App\Models\FormEvaluationScore;
use App\Models\JudgeProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EvaluationController extends Controller
{
    public function index(ListEvaluationsRequest $request): JsonResource
    {
        // Check if judge is archived
        $judge = auth()->user();
        if ($judge && method_exists($judge, 'isArchived') && $judge->isArchived()) {
            abort(401, 'Account has been archived');
        }

        $assignment = JudgeProject::where('judge_id', auth()->id())
            ->where('project_id', $request->project_id)
            ->firstOrFail();

        $evaluations = $assignment->evaluations()->active()->with(['form', 'stage'])->get();

        $grouped = $evaluations->groupBy(function ($eval) {
            return $eval->form_id . '-' . $eval->stage_id;
        });

        return EvaluationResource::collection($grouped->values());
    }

    public function store(StoreEvaluationRequest $request): JsonResource|JsonResponse
    {
        // Check if judge is archived
        $judge = auth()->user();
        if ($judge && method_exists($judge, 'isArchived') && $judge->isArchived()) {
            abort(401, 'Account has been archived');
        }

        $validated = $request->validated();
        $stageId = $validated['stage_id'];
        $formId = $validated['form_id'];
        $form = Form::findOrFail($formId);
        $evaluationConfig = $form->evaluation_config ?? [];
        $evaluationMaxScore = $evaluationConfig['total_score'] ?? 0;
        $isDisclaimerAcceptance = $evaluationConfig['require_agreement_acceptance'] ?? false;
        $roundingRule = $evaluationConfig['rounding_rule'] ?? 2;

        $assignment = JudgeProject::where('judge_id', auth()->id())
            ->where('project_id', $validated['project_id'])
            ->firstOrFail();

        // Check if evaluation has already been submitted to prevent modification
        // An active (non-archived) FormEvaluationScore indicates a submitted evaluation
        $existingSubmittedEvaluation = FormEvaluationScore::where('judge_project_id', $assignment->id)
            ->where('form_id', $formId)
            ->where('stage_id', $stageId)
            ->where('is_archived', false)
            ->first();

        if ($existingSubmittedEvaluation) {
            return response()->json([
                'error' => 'evaluation_already_submitted',
                'message' => 'This evaluation has already been submitted and cannot be modified. / تم إرسال هذا التقييم بالفعل ولا يمكن تعديله.',
            ], 403);
        }

        if ($isDisclaimerAcceptance == true){
            $disclaimerAcceptance = DisclaimerAcceptance::where('judge_id', auth()->id())
                ->where('stage_id', $stageId)
                ->where('form_id', $formId)
                ->first();

            if (!$disclaimerAcceptance || !$disclaimerAcceptance->accepted) {
                return response()->json([
                    'error' => 'disclaimer_not_accepted',
                    'message' => 'You must accept the disclaimer before submitting evaluations. / يجب عليك قبول إخلاء المسؤولية قبل إرسال التقييمات.',
                ], 403);
            }

        }


        $finalComment = $validated['answers']['final_comment'] ?? null;

        $assignment->final_comment = $finalComment;
        $assignment->save();

        $config = $evaluationConfig['evaluation_criteria'] ?? [];

        $questionWeights = collect();

        foreach ($config as $criteria) {
            if (empty($criteria['slug']) || !isset($criteria['weight'])) {
                continue;
            }

            $mainLabel = trim($criteria['slug']);
            $mainWeight = (float) $criteria['weight'];
            $questionWeights[$mainLabel] = $mainWeight;

            $subcriteria = $criteria['subcriteria'] ?? [];

            if (!empty($subcriteria)) {
                $totalSubWeight = collect($subcriteria)->sum(fn($sub) => isset($sub['weight']) ? (float) $sub['weight'] : 0);

                if ($totalSubWeight > 0) {
                    foreach ($subcriteria as $sub) {
                        if (empty($sub['slug']) || !isset($sub['weight'])) {
                            continue;
                        }

                        $subLabel = trim($sub['slug']);
                        $subWeight = (float) $sub['weight'];
                        $relativeWeight = $mainWeight * ($subWeight / $totalSubWeight);

                        $uniqueKey = "{$mainLabel}_{$subLabel}";
                        $questionWeights[$uniqueKey] = $relativeWeight;
                    }
                }
            }
        }

        $rawAnswers = $validated['answers'];

        $answers = Arr::except(
            $rawAnswers,
            array_filter(
                array_keys($rawAnswers),
                fn($key) => Str::endsWith($key, '_questions') || Str::endsWith($key, '_comment')
            )
        );

        // Helper function to find question config in evaluation criteria
        $findQuestionConfig = function ($questionSlug, $config) {
            foreach ($config as $criteria) {
                // Check main criterion
                if (($criteria['slug'] ?? '') === $questionSlug) {
                    return $criteria;
                }
                // Check subcriteria
                if (!empty($criteria['subcriteria'])) {
                    foreach ($criteria['subcriteria'] as $sub) {
                        $mainSlug = $criteria['slug'] ?? '';
                        $subSlug = $sub['slug'] ?? '';
                        $combinedSlug = "{$mainSlug}_{$subSlug}";
                        if ($combinedSlug === $questionSlug) {
                            return $sub;
                        }
                    }
                }
            }
            return null;
        };

        // Helper function to calculate multiple choice score
        $calculateMultipleChoiceScore = function ($selectedOptions, $questionConfig) {
            $options = $questionConfig['multiple_choice_options'] ?? [];
            $selectionType = $questionConfig['selection_type'] ?? 'single';
            $isRequired = $questionConfig['required'] ?? false;

            // Validate required questions
            if ($isRequired && (empty($selectedOptions) || (is_array($selectedOptions) && count($selectedOptions) === 0))) {
                return [
                    'error' => true,
                    'message' => 'This question is required. Please select at least one option. / هذا السؤال مطلوب. يرجى اختيار خيار واحد على الأقل.',
                ];
            }

            // If nothing selected, return 0
            if (empty($selectedOptions) || (is_array($selectedOptions) && count($selectedOptions) === 0)) {
                $rawScore = 0;
            } else {
                // Calculate raw score from selected options
                $selectedIndices = is_array($selectedOptions) ? $selectedOptions : [$selectedOptions];
                $rawScore = 0;
                
                foreach ($selectedIndices as $index) {
                    if (isset($options[$index]) && isset($options[$index]['points'])) {
                        $rawScore += (float) $options[$index]['points'];
                    }
                }
            }

            // Calculate maximum possible score
            $maxScore = 0;
            if ($selectionType === 'single') {
                // For single selection, max is the highest point value
                foreach ($options as $option) {
                    $points = (float) ($option['points'] ?? 0);
                    if ($points > $maxScore) {
                        $maxScore = $points;
                    }
                }
            } else {
                // For multiple selections, max is sum of all positive point values
                foreach ($options as $option) {
                    $points = (float) ($option['points'] ?? 0);
                    if ($points > 0) {
                        $maxScore += $points;
                    }
                }
            }

            // Normalize to 0-100 scale
            if ($maxScore > 0) {
                $normalizedScore = ($rawScore / $maxScore) * 100;
            } else {
                $normalizedScore = 0;
            }

            // Ensure score is between 0 and 100
            $normalizedScore = max(0, min(100, $normalizedScore));

            return [
                'error' => false,
                'raw_score' => $rawScore,
                'max_score' => $maxScore,
                'normalized_score' => $normalizedScore,
                'selected_options' => is_array($selectedOptions) ? $selectedOptions : [$selectedOptions],
            ];
        };

        // Validate all answer values are within valid range (0-100) as defense-in-depth
        foreach ($answers as $question => $answer) {
            // Skip final_comment as it's not a numeric answer
            if ($question === 'final_comment') {
                continue;
            }
            
            // Check if this is a multiple choice question
            $questionConfig = $findQuestionConfig($question, $config);
            if ($questionConfig && ($questionConfig['scoring_method'] ?? '') === 'multiple_choice') {
                // Validate multiple choice answer
                $scoreResult = $calculateMultipleChoiceScore($answer, $questionConfig);
                if ($scoreResult['error']) {
                    return response()->json([
                        'error' => 'validation_error',
                        'message' => $scoreResult['message'],
                        'field' => $question,
                    ], 422);
                }
            } elseif (is_numeric($answer) || (is_string($answer) && is_numeric($answer))) {
                $numericAnswer = (float) $answer;
                // Validate that the answer is between 0 and 100
                // Use strict comparison to avoid floating point issues
                if ($numericAnswer < 0 || $numericAnswer > 100) {
                    // Allow very small tolerance for floating point errors (0.0001)
                    $tolerance = 0.0001;
                    if ($numericAnswer < (0 - $tolerance) || $numericAnswer > (100 + $tolerance)) {
                        return response()->json([
                            'error' => 'invalid_evaluation_score',
                            'message' => "Evaluation score for '{$question}' must be between 0 and 100. / يجب أن تكون درجة التقييم بين 0 و 100.",
                            'field' => $question,
                            'value' => $answer,
                        ], 422);
                    }
                    // Clamp value to valid range if within tolerance
                    $numericAnswer = max(0, min(100, $numericAnswer));
                }
            }
        }

           collect($answers)->map(function ($answer, $question) use ($assignment, $request, $formId, $questionWeights, $stageId, $config, $findQuestionConfig, $calculateMultipleChoiceScore) {
            $subquestions = $request->input("answers.{$question}_questions");
            $comment = $request->input("answers.{$question}_comment");

            $weight = $questionWeights[$question] ?? 0;
            
            // Check if this is a multiple choice question
            $questionConfig = $findQuestionConfig($question, $config);
            $selectedOptions = null;
            
            if ($questionConfig && ($questionConfig['scoring_method'] ?? '') === 'multiple_choice') {
                // Calculate normalized score for multiple choice
                $scoreResult = $calculateMultipleChoiceScore($answer, $questionConfig);
                $answer = $scoreResult['normalized_score'];
                $selectedOptions = $scoreResult['selected_options'];
                // Store selected options in details field
                $subquestions = $selectedOptions;
            } elseif (is_numeric($answer)) {
                // Validate answer is numeric (validation should have caught invalid values already)
                // Convert to float for storage, defaulting to 0 if not numeric
                $answer = (float) $answer;
            } else {
                $answer = 0;
            }

            // First, check if there's an existing active evaluation
            $existingEvaluation = $assignment->evaluations()
                ->where('form_id', $formId)
                ->where('stage_id', $stageId)
                ->where('question', $question)
                ->where('is_archived', false)
                ->first();

            if ($existingEvaluation) {
                // Update existing active evaluation
                return $existingEvaluation->update([
                    'details' => is_array($subquestions)
                        ? json_encode($subquestions, JSON_UNESCAPED_UNICODE)
                        : ($subquestions ?? null),
                    'comment' => is_string($comment)
                        ? $comment
                        : (is_array($comment) ? json_encode($comment, JSON_UNESCAPED_UNICODE) : null),
                    'answer' => is_numeric($answer) ? (float) $answer : 0,
                    'weight' => $weight,
                ]);
            } else {
                // Create new evaluation (this handles the case where previous evaluation was archived)
                return $assignment->evaluations()->create([
                    'form_id' => $formId,
                    'stage_id' => $stageId,
                    'question' => $question,
                    'details' => is_array($subquestions)
                        ? json_encode($subquestions, JSON_UNESCAPED_UNICODE)
                        : ($subquestions ?? null),
                    'comment' => is_string($comment)
                        ? $comment
                        : (is_array($comment) ? json_encode($comment, JSON_UNESCAPED_UNICODE) : null),
                    'answer' => is_numeric($answer) ? (float) $answer : 0,
                    'weight' => $weight,
                    'is_archived' => false,
                ]);
            }

        });


        // Recalculate total evaluation score from all evaluations
        $allEvaluations = $assignment->evaluations()
            ->active()
            ->where('form_id', $formId)
            ->where('stage_id', $stageId)
            ->with('form')
            ->get();

        // Calculate the total score by summing: answer * weight for each criterion
        $totalScore = $allEvaluations->sum(fn($eval) => ($eval->answer / 100) * $eval->weight);

        // The final score is the total score
        $finalScore = round($totalScore, $roundingRule);


        // Save score for this specific form/stage
        // First check if there's an existing active score
        $existingScore = FormEvaluationScore::where('judge_project_id', $assignment->id)
            ->where('form_id', $formId)
            ->where('stage_id', $stageId)
            ->where('is_archived', false)
            ->first();

        if ($existingScore) {
            // Update existing active score
            $existingScore->update([
                'evaluation_score' => $finalScore,
            ]);
        } else {
            // Create new score (this handles the case where previous score was archived)
            FormEvaluationScore::create([
                'judge_project_id' => $assignment->id,
                'form_id' => $formId,
                'stage_id' => $stageId,
                'evaluation_score' => $finalScore,
                'is_archived' => false,
            ]);
        }

        $assignment->update([
            'evaluation_score' => $finalScore,
        ]);

        $assignment->project->updateScore();

        $evaluations = $assignment->evaluations()->active()->with(['form', 'stage'])->get();
        $grouped = $evaluations->groupBy(function ($eval) {
            return $eval->form_id . '-' . $eval->stage_id;
        });

        return EvaluationResource::collection($grouped->values());
    }

}
