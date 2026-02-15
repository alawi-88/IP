<?php

namespace App\Http\Resources;

use App\Models\DisclaimerAcceptance;
use App\Models\Project;
use App\Models\Form;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class StageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $formIds = $this->getFormIds();
        $stageId = $this->id;
        $userId = auth()->id();

        // Get forms by IDs
       // $forms = Form::whereIn('id', $formIds)->select('id', 'name')->get();
        // Get all submitted projects for the current participant/team across all forms
        // First, try to find projects for individual applications or team leader
        // (Team leaders are the participant_id in the application)
        $projects = Project::whereIn('projects.form_id', $formIds)
            ->join('competition_applications', 'projects.application_id', '=', 'competition_applications.id')
            ->where('competition_applications.participant_id', $userId)
            ->where('projects.is_archived', false)
            ->where('projects.type', 'submission') // Only check for submitted projects, not drafts
            ->orderBy('projects.created_at', 'desc')
            ->select('projects.*')
            ->get();

        // If not found, check if user is a team member and find projects for their team
        // This handles the case where team members need to see if their team's projects are already submitted
        if ($projects->isEmpty()) {
            $projects = Project::whereIn('projects.form_id', $formIds)
                ->join('competition_applications', 'projects.application_id', '=', 'competition_applications.id')
                ->join('teams', 'competition_applications.id', '=', 'teams.application_id')
                ->join('team_members', 'teams.id', '=', 'team_members.team_id')
                ->where('team_members.participant_id', $userId)
                ->where('projects.is_archived', false)
                ->where('projects.type', 'submission') // Only check for submitted projects
                ->orderBy('projects.created_at', 'desc')
                ->select('projects.*')
                ->get();
        }

        // Get project IDs for all submitted projects
        $projectIds = $projects->pluck('id')->filter()->values();

        // Check if at least one project is submitted
        $isSubmitted = $projects->isNotEmpty();

        // Get evaluations for all forms
        $evaluations = ProjectEvaluation::whereIn('form_id', $formIds)
            ->where('stage_id', $stageId)
            ->whereHas('judgeProject', function ($query) {
                $query->where('judge_id', auth()->id());
            })
            ->latest()
            ->get();

        // Get project IDs from evaluations
        $evaluationProjectIds = ProjectEvaluation::whereIn('form_id', $formIds)
            ->where('stage_id', $stageId)
            ->whereHas('judgeProject', function ($query) {
                $query->where('judge_id', auth()->id());
            })
            ->with('judgeProject')
            ->get()
            ->pluck('judgeProject.project_id')
            ->filter()
            ->unique()
            ->values();

        // Check if evaluation is submitted (at least one evaluation exists)
        $isEvaluationSubmitted = $evaluations->isNotEmpty();

        // Get the judge's assigned project for the current stage
        $disclaimerAcceptance = DisclaimerAcceptance::where('judge_id', auth()->id())
            ->where('stage_id', $stageId)->first();
            
        $forms = collect([]);
        if($formIds){
            // Get forms by IDs
            $forms = Form::whereIn('id', $formIds)->select('id', 'name')->get();
            // $projectsObj = Project::whereIn('id', $projectIds)->select('id', 'name')->get();
        }
        


        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            // Return datetime strings with precise timing
            'starts_at' => $this->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $this->ends_at?->format('Y-m-d H:i:s'),
            //'form_id' => $formIds,
            // 'project' => [
            //     'project_id' => $projectIds->isNotEmpty() ? $projectIds->toArray() : null,
            //     'isSubmitted' => $isSubmitted,
            // ],
            'forms' => $forms->map(function ($form) {
                return [
                    'id' => $form->id,
                    'name' => is_array($form->name)
                        ? ($form->name['en'] ?? reset($form->name))
                        : $form->name,
                ];
            }),
            'projects'=> $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'isSubmitted' => $project->type === 'submission' ? true : false,
                ];
            }),
            // 'projects' => $projectsObj->map(function ($project) {
            //     return [
            //         'id' => $project->id,
            //         'isSubmitted' => $project->isSubmitted(),
            //     ];
            // }),
            'evaluation' => [
                'project_id' => $evaluationProjectIds->isNotEmpty() ? $evaluationProjectIds->toArray() : null,
                'isSubmitted' => $isEvaluationSubmitted,
                'isDisclaimerAccepted' => $disclaimerAcceptance?->accepted ?? null,
            ],
        ];
    }
}
