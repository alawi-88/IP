<?php

namespace App\Http\Resources;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationsFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $aiEnhancementConfig = $this->aiEnhancementConfig;
        $aiEnhancementEnabled = $aiEnhancementConfig && $aiEnhancementConfig->isEnhancementEnabled();

        // Build enhancement fields with instructions
        $enhancementFields = null;
        if ($aiEnhancementEnabled && $aiEnhancementConfig) {
            $fieldsData = $aiEnhancementConfig->ai_enhancement_fields ?? [];
            
            if (!empty($fieldsData) && is_array($fieldsData)) {
                // Check if it's new format (array of objects with slug and instructions)
                $isNewFormat = isset($fieldsData[0]) && is_array($fieldsData[0]) && isset($fieldsData[0]['slug']);
                
                if ($isNewFormat) {
                    // New format: return fields with instructions
                    $enhancementFields = array_map(function ($fieldConfig) {
                        return [
                            'slug' => $fieldConfig['slug'] ?? null,
                            'instructions' => $fieldConfig['instructions'] ?? null,
                        ];
                    }, $fieldsData);
                } else {
                    // Legacy format: array of slugs - convert to new format with null instructions
                    $enhancementFields = array_map(function ($slug) {
                        return [
                            'slug' => $slug,
                            'instructions' => null,
                        ];
                    }, $fieldsData);
                }
            }
        }

        return [
            'id' => $this->id,
            'program' => new ProgramResource($this->program),
            'type' => $this->type,
            'name' => $this->name,
            'description' => ! empty($this->description) ? $this->description : null,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'evaluation_config' => $this->localized_evaluation_config,
            'evaluation_stages' => $this->evaluation_stages,
            'ai_enhancement' => [
                'enabled' => $aiEnhancementEnabled,
            ],
        ];
    }
}
