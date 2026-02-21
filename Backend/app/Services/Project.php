<?php

namespace App\Services;

use App\Models\Project as ProjectModel;
use App\Models\Form;
use App\Models\ProgramApplication;
use App\Notifications\ProjectSubmitted;
use App\Jobs\ProcessProjectAiEvaluation;
use App\Models\FormAiScoringConfig;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Project
{
    public function list($request)
    {
        //dd($request->all());
        return ProjectModel::where('application_id',$request->application_id)
            ->active() // Only show non-archived projects
            ->get();
    }

    public function existsForTeam($teamId): bool
    {
        return ProjectModel::where('team_id', $teamId)
            ->active() // Only check for non-archived projects
            ->exists();
    }

    public function store(array $data): ProjectModel
    {
        $answers = $data['answers'] ?? [];

        foreach ($answers ?? [] as $key => $value) {
            // Check if a file is being uploaded for this answer key
            $file = request()->file("answers.$key");

            if ($file instanceof \Illuminate\Http\UploadedFile) {
                // Handle file upload
                if ($file->isValid()) {
                    $path = $file->store('uploads/files', 'public');
                    $answers[$key] = $path;
                } else {
                    throw ValidationException::withMessages([
                        "answers.$key" => "The file upload failed. Please try again.",
                    ]);
                }
            } elseif (is_string($value) && !empty($value)) {
                // Handle string value (could be a file path/URL from previous upload or text input)
                // Remove full URL prefix if present (e.g., https://domain.com/storage/)
                $normalizedValue = preg_replace('#^https?://[^/]+/storage/#', '', $value);
                
                // If it looks like a file path (starts with uploads/files/), keep it as is
                // Otherwise, treat it as a regular text value
                if (preg_match('#^uploads/files/#', $normalizedValue)) {
                    $answers[$key] = $normalizedValue;
                } else {
                    // For non-file string values, keep as is (text inputs, etc.)
                    $answers[$key] = $value;
                }
            } elseif (is_object($value)) {
                // If the value is an object (e.g., [object Object] from frontend), set to null
                $answers[$key] = null;
            } else {
                // For null, empty, or other types, set to null
                $answers[$key] = null;
            }
        }


        $team =  \App\Models\Team::where('application_id',$data['application_id'])->first();

        // Check if this is a submission (not a draft) and if user is team leader
        $submissionType = $data['type'] ?? 'draft';
        if ($submissionType === 'submission' && $team) {
            // Check if the current user is the team leader
            $isTeamLeader = $team->members()
                ->where('participant_id', auth()->id())
                ->where('is_leader', true)
                ->exists();

            if (!$isTeamLeader) {
                throw ValidationException::withMessages([
                    'type' => __('project.only_team_leader_can_submit', [
                        'default' => 'Only the team leader can submit the project. / يمكن لقائد الفريق فقط إرسال المشروع.'
                    ]),
                ]);
            }
        }

        $data['application_id'] = $data['application_id'];
        $data['form_id'] = $data['form_id'];
        $data['team_id'] = $team->id ?? null;
        $data['form_submissions'] = $answers ?? null;

        try {
            $existingProject = ProjectModel::where('form_id', $data['form_id'] ?? null)
                ->where('application_id', $data['application_id'] ?? null)
                ->first();

            if ($existingProject) {
                if($existingProject->type === 'submission'){
                    // If the project is archived, restore it and update with new submission data
                    if ($existingProject->isArchived()) {
                        $existingProject->restore();
                        // Update with new submission data
                        $existingProject->update($data);
                        // Reset status to pending for resubmission
                        $existingProject->setStatusAs('pending');
                        $project = $existingProject->fresh();
                    } else {
                        // If not archived, return existing submission (no changes allowed)
                        return $existingProject;
                    }
                }elseif($existingProject->type === 'draft'){
                    // If draft is archived, restore it first
                    if ($existingProject->isArchived()) {
                        $existingProject->restore();
                    }
                    // Merge new answers with existing form_submissions to preserve data from other steps
                    $existingAnswers = $existingProject->form_submissions ? $existingProject->form_submissions->toArray() : [];
                    $mergedAnswers = array_merge($existingAnswers, $answers);
                    $data['form_submissions'] = $mergedAnswers;
                    
                    $existingProject->update($data);
                    $project = $existingProject->fresh();
                }
            }else{
                $project = ProjectModel::create($data);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Log database errors with full details server-side
            \Log::error('Database error in project store', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Throw a generic exception without exposing SQL or file paths
            throw new \Exception('An error occurred while saving your project. Please check your input and try again.');
        }
        
        // Send notification to participant
        if($project->type != 'draft'){
            if ($project->team) {
                foreach ($project->team->members as $member) {
                    $participant = $member->participant;
                    if ($participant) {
                        $participant->notify(new ProjectSubmitted($project));
                    }
                }
            } else {
                auth()->user()->notify(new ProjectSubmitted($project));
            }
        }

        // Trigger AI evaluation automatically for submitted projects when configured
        if ($project->type === 'submission') {
            $hasAiConfig = FormAiScoringConfig::where('form_id', $project->form_id)->exists();

            if ($hasAiConfig) {
                // Mark AI evaluation as pending and dispatch the job
                $project->update([
                    'ai_evaluation_response' => [
                        'status' => 'pending',
                        'message' => 'AI evaluation is being processed.',
                    ],
                    'ai_evaluated_at' => null,
                ]);

                ProcessProjectAiEvaluation::dispatch($project->id);
            } else {
                // Explicitly record that AI is not configured for this form
                $project->update([
                    'ai_evaluation_response' => [
                        'status' => 'skipped',
                        'message' => 'AI evaluation is not configured for this form.',
                    ],
                    'ai_evaluated_at' => null,
                ]);
            }
        }

        return $project;
    }
}
