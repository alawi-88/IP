<?php

namespace App\Http\Resources;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListProjectsFormResource extends JsonResource
{
    protected ?array $answers = [];

    public function __construct($resource, $answers = [])
    {
        parent::__construct($resource);
        $this->answers = $answers;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $steps = $this->ProjectSteps;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => ! empty($this->description) ? $this->description : null,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'fields' => $steps->isEmpty()
                ? $this->fields->map(fn ($field) => new FormFieldResource($field, $this->answers))
                : [],
            'steps' => $steps->isEmpty()
                ? []
                : $steps->map(function ($step) {
                    $fieldsInStep = $this->fields->whereIn('id', $step->field_ids->map(fn($id) => (int)$id));
                    return [
                        'id' => $step->id,
                        'name' => $step->name,
                        'step_order' => $step->step_order,
                        'fields' => $fieldsInStep
                            ->map(fn ($field) => (new FormFieldResource($field, $this->answers))->toArray(request()))
                            ->values(),
                    ];
                }),
        ];
    }
}
