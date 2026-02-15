<?php

namespace App\Http\Resources;

use App\Models\CompetitionApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationFormResource extends JsonResource
{
    protected ?array $answers = [];
    protected ?string $submitType = null;
    protected ?int $applicationId = null;

    public function __construct($resource, $answers = [], $submitType = null, $applicationId = null)
    {
        parent::__construct($resource);
        $this->answers = $answers;
        $this->submitType = $submitType;
        $this->applicationId = $applicationId;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $steps = $this->FormSteps;

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
            'type' => $this->type,
            'application_id' => $this->applicationId,
            'submit_type' => $this->submitType ?? 'submission',
            'name' => $this->name,
            'description' => ! empty($this->description) ? $this->description : null,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'competition' => new CompetitionResource($this->competition),
            'ai_enhancement' => [
                'enabled' => $aiEnhancementEnabled,
            ],
            'fields' => $steps->isEmpty()
                ? $this->fields->map(fn ($field) => new FormFieldResource($field, $this->answers, $aiEnhancementConfig))
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
                            ->map(fn ($field) => (new FormFieldResource($field, $this->answers, $aiEnhancementConfig))->toArray(request()))
                            ->values(),
                    ];
                }),
            'team' => [
                'register_as' => $this->answers['register_as'] ?? $this->registered_as ?? null,
                'team_name'   => $this->answers['team_name'] ?? $this->team_name ?? null,
                'team_logo'   => $this->formatTeamLogo($this->answers['team_logo'] ?? $this->team_logo ?? null),
                'team_serial' => $this->formatTeamSerial($this->answers['team_serial'] ?? $this->team_serial ?? null),
                'has_team'    => $this->answers['has_team'] ?? null,
            ],
        ];
    }

    /**
     * Format team_logo to handle empty arrays
     */
    private function formatTeamLogo($teamLogo): ?string
    {
        if (is_null($teamLogo)) {
            return null;
        }

        if (is_string($teamLogo) && !empty($teamLogo)) {
            return asset('storage/' . $teamLogo);
        }

        if (is_array($teamLogo)) {
            // If it's an empty array, return null
            if (empty($teamLogo)) {
                return null;
            }
            // If it has values, return the first one (assuming single logo)
            return !empty($teamLogo[0]) ? asset('storage/' . $teamLogo[0]) : null;
        }

        return null;
    }

    /**
     * Format team_serial to string
     */
    private function formatTeamSerial($teamSerial): ?string
    {
        if (is_null($teamSerial)) {
            return null;
        }

        if (is_string($teamSerial)) {
            return $teamSerial;
        }

        if (is_array($teamSerial)) {
            return implode(',', array_filter($teamSerial));
        }

        return null;
    }
}
