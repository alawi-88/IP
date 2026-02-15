<?php

namespace App\Http\Resources;

use App\Models\JudgeProject;
use App\Models\Participant;
use App\Models\ProjectEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,

            'has_comment' => (bool) $this->comments()
                ->where('user_id', '!=', null)
                ->whereNull('author_type')
                ->where('is_read', false)
                ->count(),

            'is_evaluated' => $this->when(auth('judges')->check(), function () {
                $judgeId = auth('judges')->user()?->id;

                $judgeProjectId = JudgeProject::where('project_id', $this->id)
                    ->where('judge_id', $judgeId)
                    ->first()?->id;

                if (!$judgeProjectId) {
                    return false;
                }

                // Ensure stage_id is part of the condition and filter out archived evaluations
                return ProjectEvaluation::where('judge_project_id', $judgeProjectId)
                    ->where('stage_id',$this->competition?->currentStage()?->id) // replace with actual stage context
                    ->where('is_archived', false) // Filter out archived evaluations
                    ->exists();
            }),

            'evaluations' => $this->when(
                $this->evaluations()->where('is_archived', false)->get()->isNotEmpty(),
                function () {
                    $evaluations = $this->evaluations()->where('is_archived', false)->get();
                    $grouped = $evaluations->groupBy(function ($eval) {
                        return $eval->form_id . '-' . $eval->stage_id;
                    });

                    return EvaluationResource::collection($grouped->values());
                }
            ),

            'competition' => new CompetitionResource($this->competition, $this->id),
            'form_id'        => $this->form_id,
            'form'           => new ListProjectsFormResource(
                $this->form,
                ($this->form_submissions instanceof SchemalessAttributes)
                    ? $this->form_submissions->toArray()
                    : []
            ),
            'metadata' => [
                'project_name' => $this->form_submissions?->project_name,
                'participant_name' => $this->application?->participant?->name,
            ],
            'team' => new TeamResource($this->team),
            'submit_type' => $this->type ?? 'submission',
        ];
    }
}
