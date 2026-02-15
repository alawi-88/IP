<?php

namespace App\Http\Resources;

use App\Models\CompetitionApplication;
use App\Models\Form;
use App\Models\Project;
use App\Models\Stage;
use App\Models\ProjectEvaluation;
use App\Models\JudgeProject;
use App\Models\DisclaimerAcceptance;
use App\Models\FormEvaluationScore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\BrandingCompetition;
use Illuminate\Support\Str;

class CompetitionResource extends JsonResource
{
    protected $projectId;
    protected $applicationId;

    public function __construct($resource, $projectId = null, $applicationId = null)
    {
        parent::__construct($resource);
        $this->projectId = $projectId;
        $this->applicationId = $applicationId;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $forms = Form::where('competition_id', $this->id)->pluck('id');

        $project = $this->projectId ? Project::find($this->projectId) : null;

        $competitionApplications = $this->applicationId
            ? CompetitionApplication::find($this->applicationId)
            : CompetitionApplication::whereIn('form_id', $forms)->latest()->first();

        $selectedTrackId = $project?->form_submissions->track ?? $competitionApplications?->form_submissions->track;
        $selectedSubTrackId = $project?->form_submissions->sub_track  ?? $competitionApplications?->form_submissions->sub_track;

        // $selectedTrackId = $competitionApplications?->form_submissions->track;
        // $selectedSubTrackId = $competitionApplications?->form_submissions->sub_track;
        $type = null;
        if(isset($this->type) && !empty($this->type)){
            $type= [
                'title' => Str::title($this->type),
                'slug' => Str::slug($this->type),
            ];
        }
        $stageForms = collect();
        $stageProjects = collect();

        $currentStage = $this->currentStage();
        $currentStageId = $currentStage?->id;
        $evaluationProjectIds = collect();
        $isEvaluationSubmitted = false;
        $disclaimerAcceptance = null;

        if ($currentStage) {
            $formIds = $currentStage->getFormIds();
            $stageForms = Form::whereIn('id', $formIds)->get();

            // Get projects for these forms if application context exists
            if ($competitionApplications) {
                $stageProjects = Project::where('application_id', $competitionApplications->id)
                    ->whereIn('form_id', $formIds)
                    ->get();
            }

            // Check if judge has submitted evaluation for current stage (similar to StageResource logic)
            if (auth('judges')->check() && $currentStageId && !empty($formIds)) {
                $judgeId = auth('judges')->user()?->id;

                // Get evaluations for the current stage
                $evaluations = ProjectEvaluation::whereIn('form_id', $formIds)
                    ->where('stage_id', $currentStageId)
                    ->whereHas('judgeProject', function ($query) use ($judgeId) {
                        $query->where('judge_id', $judgeId);
                    })
                    ->where('is_archived', false)
                    ->with('judgeProject')
                    ->latest()
                    ->get();

                // Get project IDs from evaluations
                $evaluationProjectIds = $evaluations
                    ->pluck('judgeProject.project_id')
                    ->filter()
                    ->unique()
                    ->values();

                // Check if evaluation is submitted (at least one active evaluation exists)
                // Also check FormEvaluationScore as it indicates a submitted evaluation
                $isEvaluationSubmitted = $evaluations->isNotEmpty() ||
                    FormEvaluationScore::whereIn('form_id', $formIds)
                        ->where('stage_id', $currentStageId)
                        ->whereHas('judgeProject', function ($query) use ($judgeId) {
                            $query->where('judge_id', $judgeId);
                        })
                        ->where('is_archived', false)
                        ->exists();

                // Get disclaimer acceptance for the current stage
                $disclaimerAcceptance = DisclaimerAcceptance::where('judge_id', $judgeId)
                    ->where('stage_id', $currentStageId)
                    ->first();
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $type,
            'about' => $this->about,
            'terms_and_conditions' => $this->terms_and_conditions,
            'registration_opened_date' => $this->registrationStage()?->starts_at?->format('Y-m-d H:i:s'),
            'registration_closed_date' => $this->registrationStage()?->ends_at?->format('Y-m-d H:i:s'),
            'banner' => Storage::url($this->banner),
            'is_closed' => $this->isClosed(),
            'is_published' => $this->isPublished(),
            'current_stage_slug' => $currentStage?->slug,
            'current_stage_id' => $currentStage?->id,
            'current_stage' => $currentStage ? [
                //'formIds' => $currentStage->getFormIds(),
                'id' => $currentStage->id,
                'slug' => $currentStage->slug,
                'title' => $currentStage->title,
                'description' => $currentStage->description,
                'starts_at' => optional($currentStage->starts_at)?->format('Y-m-d H:i:s'),
                'ends_at' => optional($currentStage->ends_at)?->format('Y-m-d H:i:s'),
                'forms' => $stageForms->map(function ($form) {
                    return [
                        'id' => $form->id,
                        'name' => $form->name,
                    ];
                }),
                'projects' => $stageProjects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name ?? $project->form_submissions['project_name'] ?? 'Untitled',
                        'isSubmitted' => (bool) $project->isSubmitted, // Accessor or property might need check
                    ];
                }),
                'evaluation' => [
                    'project_id' => auth('judges')->check() && $evaluationProjectIds->isNotEmpty()
                        ? $evaluationProjectIds->toArray()
                        : $stageProjects->pluck('id')->toArray(),
                    'isSubmitted' => auth('judges')->check()
                        ? $isEvaluationSubmitted
                        : (bool) ($stageProjects->first()?->isSubmitted ?? false),
                    'isDisclaimerAccepted' => auth('judges')->check()
                        ? ($disclaimerAcceptance?->accepted ?? null)
                        : ($stageProjects->first()?->isDisclaimerAccepted ?? null),
                ],
            ] : null,
            //'current_stage2' => $this->currentStage() ? new StageResource($this->currentStage()) : null,
            'stages' => StageResource::collection($this->stages()->where('is_visible', true)->get()),
            'hub' => $this->tabs,
            'selectedTrackId' => $selectedTrackId,
            'selectedSubTrackId' => $selectedSubTrackId,
            'tracks' => $this->tracks->map(function ($track) use ($selectedTrackId, $selectedSubTrackId) {
                return [
                    'id' => $track->id,
                    'slug' => $track->slug,
                    'name' => $track->name,
                    'order' => $track->order,
                    'is_selected' => $track->id == $selectedTrackId,
                    'sub_tracks' => optional($track->subTracks)->map(function ($sub_track) use ($selectedSubTrackId) {
                        return [
                            'id' => $sub_track->id,
                            'slug' => $sub_track->slug,
                            'name' => $sub_track->name,
                            'order' => $sub_track->order,
                            'is_selected' => $sub_track->id == $selectedSubTrackId,
                        ];
                    }) ?? [],
                ];
            }),
            'branding' => BrandingCompetition::getByCompetitionId($this->id) ?? null,
        ];
    }
}
