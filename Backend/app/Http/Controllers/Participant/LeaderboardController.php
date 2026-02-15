<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardResource;
use App\Models\CompetitionApplication;
use App\Models\FormEvaluationScore;
use App\Models\Project;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Display the leaderboard for the current competition.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $participantId = auth()->id();

            // Prefer competition_id from request, then fallback to session
            $competitionId = $request->query('competition_id');

            if (!$competitionId) {
                return response()->json([
                    'success' => false,
                    'message' => __('leaderboard.competition_not_found'),
                ], 404);
            }

            // Get all approved applications for this competition
            $applications = CompetitionApplication::where('competition_id', $competitionId)
                ->where('status', 'approved')
                ->where('is_archived', false)
                ->where('type', 'submission')
                ->with(['participant', 'team'])
                ->get();

            // Get all stages for this competition (excluding registration and team-formation stages)
            $stages = Stage::where('competition_id', $competitionId)
                ->where('is_visible', true)
                ->where('slug', '!=', 'registration')
                ->where('slug', '!=', 'team-formation')
                ->orderBy('starts_at')
                ->get();

            // Get all evaluation stages (stages with slug 'evaluation' or starting with 'evaluation')
            $evaluationStages = $stages->filter(function ($stage) {
                return $stage->slug === 'evaluation' || 
                       (is_string($stage->slug) && str_starts_with($stage->slug, 'evaluation'));
            });

            // Build leaderboard data
            $leaderboard = [];

            foreach ($applications as $application) {
                // Calculate registration score (Assessment Scores + AI Evaluation)
                $registrationScore = (float) ($application->registration_total_score ?? 0);

                // Calculate stage scores
                $stageScores = [];
                $totalStageScore = 0;

                // Track which projects have been counted to avoid double counting
                $countedProjectIds = [];

                foreach ($stages as $stage) {
                    // Skip evaluation stages - they are handled separately below
                    $isEvaluationStage = $stage->slug === 'evaluation' || 
                                        (is_string($stage->slug) && str_starts_with($stage->slug, 'evaluation'));
                    if ($isEvaluationStage) {
                        // Initialize evaluation stage score to 0, will be calculated separately
                        $stageScores[$stage->id] = 0;
                        continue;
                    }

                    $formIds = $stage->getFormIds();

                    if (empty($formIds)) {
                        $stageScores[$stage->id] = 0;
                        continue;
                    }

                    // Get all projects for this application in this stage
                    // Exclude projects that have already been counted in previous stages
                    $projects = Project::where('application_id', $application->id)
                        ->whereIn('form_id', $formIds)
                        ->where('is_archived', false)
                        ->where('type', 'submission')
                        ->whereNotIn('id', $countedProjectIds)
                        ->get();

                    // Sum all AI project scores for this stage (Project Score - AI only)
                    $stageScore = 0;

                    foreach ($projects as $project) {
                        // Mark this project as counted
                        $countedProjectIds[] = $project->id;

                        // Calculate AI score only (for Project Score)
                        $aiScore = 0;
                        $maxTotalScore = 0;

                        // Check if AI evaluation is completed
                        $aiResponse = $project->ai_evaluation_response;
                        $aiStatus = data_get($aiResponse, 'status');
                        $hasCompletedAi = $aiStatus === 'completed' && is_array($aiResponse);

                        if ($hasCompletedAi) {
                            // AI evaluation is completed - use AI score from meta data (preferred)
                            $normalizedScore = data_get($aiResponse, 'meta.normalized_score');
                            $aiTotalScore = data_get($aiResponse, 'meta.total_score');
                            $aiMaxWeight = data_get($aiResponse, 'meta.max_weight');
                            $targetTotalWeight = data_get($aiResponse, 'meta.target_total_weight');

                            if ($normalizedScore !== null) {
                                $aiScore = (float) $normalizedScore;
                                $maxTotalScore = (float) ($targetTotalWeight ?? $aiMaxWeight ?? 0);
                            } elseif ($aiTotalScore !== null) {
                                $aiScore = (float) $aiTotalScore;
                                $maxTotalScore = (float) ($aiMaxWeight ?? 0);
                            } else {
                                // Last fallback: calculate from criteria if meta is not available
                                $criteria = data_get($aiResponse, 'data.criteria', []);
                                foreach ($criteria as $criterion) {
                                    $aiScore += (float) (data_get($criterion, 'totalScore', 0));
                                    $maxTotalScore += (float) (data_get($criterion, 'maxWeight', 0));
                                }
                            }
                        }

                        $stageScore += $aiScore;
                    }

                    $stageScores[$stage->id] = $stageScore;
                    $totalStageScore += $stageScore;
                }

                // Calculate evaluation scores from judge evaluations for each evaluation stage separately
                // Get all projects for this application
                $allProjects = Project::where('application_id', $application->id)
                    ->where('is_archived', false)
                    ->where('type', 'submission')
                    ->get();

                $totalEvaluationScore = 0;
                
                // Calculate score for each evaluation stage separately
                foreach ($evaluationStages as $evaluationStage) {
                    $stageEvaluationScore = 0;
                    
                    if ($allProjects->isNotEmpty()) {
                        foreach ($allProjects as $project) {
                            // Get FormEvaluationScore records for this specific stage
                            $formEvaluationScores = FormEvaluationScore::whereHas('judgeProject', function ($query) use ($project) {
                                    $query->where('project_id', $project->id);
                                })
                                ->where('stage_id', $evaluationStage->id)
                                ->where('is_archived', false)
                                ->where('exclude_from_calculation', false)
                                ->where('evaluation_score', '>', 0)
                                ->get();

                            if ($formEvaluationScores->isNotEmpty()) {
                                // Group by judge_project_id to get one score per judge
                                $judgeScores = $formEvaluationScores->groupBy('judge_project_id')->map(function ($scores) {
                                    // If a judge has multiple forms in the same stage, take the average of their scores
                                    return $scores->avg('evaluation_score');
                                });

                                // Calculate average score across all judges for this project in this stage
                                $judgeCount = $judgeScores->count();
                                $projectStageScore = $judgeCount > 0 ? $judgeScores->avg() : 0;
                                
                                if ($projectStageScore > 0) {
                                    $stageEvaluationScore += (float) $projectStageScore;
                                }
                            }
                        }
                    }
                    
                    // Add this evaluation stage's score to stage_scores
                    $stageScores[$evaluationStage->id] = $stageEvaluationScore;
                    $totalEvaluationScore += $stageEvaluationScore;
                }

                // Calculate total score: registration + project scores (AI) + all evaluation scores (judge)
                $totalScore = $registrationScore + $totalStageScore + $totalEvaluationScore;

                // Get team name or individual name
                $name = $application->team && $application->team->name
                    ? $application->team->name
                    : ($application->participant->name ?? '-');

                $leaderboard[] = [
                    'application_id' => $application->id,
                    'participant_id' => $application->participant_id,
                    'name' => $name,
                    'is_team' => (bool) $application->team,
                    'registration_score' => $registrationScore,
                    'stage_scores' => $stageScores,
                    'total_score' => $totalScore,
                ];
            }

            // Sort by total score (descending)
            usort($leaderboard, function ($a, $b) {
                return $b['total_score'] <=> $a['total_score'];
            });

            // Calculate ranks (handle ties)
            $rank = 1;
            $previousScore = null;
            $rankedLeaderboard = [];

            foreach ($leaderboard as $index => $entry) {
                if ($previousScore !== null && $entry['total_score'] < $previousScore) {
                    $rank = $index + 1;
                }

                $entry['rank'] = $rank;
                $rankedLeaderboard[] = $entry;
                $previousScore = $entry['total_score'];
            }

            // Format response with stage information
            $formattedLeaderboard = collect($rankedLeaderboard)->map(function ($entry) use ($stages) {
                $stageScoresFormatted = [];

                // Add all stages (including evaluation stages)
                foreach ($stages as $stage) {
                    $stageId = $stage->id;
                    $score = $entry['stage_scores'][$stageId] ?? 0;
                    
                    $stageScoresFormatted[] = [
                        'stage_id' => $stageId,
                        'stage_title' => is_array($stage->title) ? ($stage->title['en'] ?? reset($stage->title)) : $stage->title,
                        'stage_slug' => $stage->slug,
                        'score' => $score,
                    ];
                }

                return [
                    'rank' => $entry['rank'],
                    'name' => $entry['name'],
                    'is_team' => $entry['is_team'],
                    'registration_score' => $entry['registration_score'],
                    'stage_scores' => $stageScoresFormatted,
                    'total_score' => $entry['total_score'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => LeaderboardResource::collection($formattedLeaderboard),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('leaderboard.error_loading'),
            ], 500);
        }
    }
}
