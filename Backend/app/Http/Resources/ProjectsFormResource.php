<?php

namespace App\Http\Resources;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectsFormResource extends JsonResource
{
    protected ?array $answers = [];
    protected ?int $application_id = null;
    protected ?string $submit_type = null;
    protected ?array $metadata = [];
    protected ?int $project_id = null;
    public function __construct($resource, $answers = [], $application_id = null, $submit_type = null, $metadata = [], $project_id = null)
    {
        parent::__construct($resource);
        $this->answers = is_array($answers) ? $answers : [];
        $this->application_id = $application_id;
        $this->submit_type = $submit_type;
        $this->metadata = $metadata;
        $this->project_id = $project_id;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $steps = $this->ProjectSteps;

        $aiEnhancementConfig = $this->aiEnhancementConfig;
        $aiEnhancementEnabled = $aiEnhancementConfig && $aiEnhancementConfig->isEnhancementEnabled();

        // Build enhancement fields with instructions for FormFieldResource
        $enhancementFields = null;
        if ($aiEnhancementEnabled && $aiEnhancementConfig) {
            $fieldsData = $aiEnhancementConfig->ai_enhancement_fields ?? [];
            
            if (!empty($fieldsData) && is_array($fieldsData)) {
                // Check if it's new format (array of objects with slug and instructions)
                $isNewFormat = isset($fieldsData[0]) && is_array($fieldsData[0]) && isset($fieldsData[0]['slug']);
                
                if ($isNewFormat) {
                    // New format: return fields with instructions and context
                    $enhancementFields = array_map(function ($fieldConfig) {
                        return [
                            'slug' => $fieldConfig['slug'] ?? null,
                            'instructions' => $fieldConfig['instructions'] ?? null,
                            'context' => $fieldConfig['context'] ?? null,
                        ];
                    }, $fieldsData);
                } else {
                    // Legacy format: array of slugs - convert to new format with null instructions and context
                    $enhancementFields = array_map(function ($slug) {
                        return [
                            'slug' => $slug,
                            'instructions' => null,
                            'context' => null,
                        ];
                    }, $fieldsData);
                }
            }
        }

        $aiEnhancementConfig = [
            'enabled' => $aiEnhancementEnabled,
            'fields' => $enhancementFields,
        ];

        return [
            'id' => $this->id,
            'competition' => new CompetitionResource($this->competition, $this->project_id, $this->application_id),
            'metadata' => $this->metadata,
            'submit_type' => $this->submit_type,
            'type' => $this->type,
            'name' => $this->name,
            'description' => ! empty($this->description) ? $this->description : null,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'ai_enhancement' => [
                'enabled' => $aiEnhancementEnabled,
            ],
            'fields' => $steps->isEmpty()
                ? $this->fields->map(fn($field) => new FormFieldResource($field, $this->answers, $aiEnhancementConfig))
                : [],
            'steps' => $steps->isEmpty()
                ? []
                : $steps->map(function ($step) use ($aiEnhancementConfig) {
                    $fieldsInStep = $this->fields->whereIn('id', $step->field_ids->map(fn($id) => (int)$id));
                    return [
                        'id' => $step->id,
                        'name' => $step->name,
                        'step_order' => $step->step_order,
                        'fields' => $fieldsInStep
                            ->map(fn($field) => (new FormFieldResource($field, $this->answers, $aiEnhancementConfig))->toArray(request()))
                            ->values(),
                    ];
                }),
        ];
    }
}