<?php

namespace App\Services;

use App\Models\FormAiScoringConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEvaluationService
{
    /**
     * Build payload and send to AI evaluation API.
     */
    public function evaluate(int $formId, array $answers, ?int $entityId = null, ?string $entityType = null): array
    {
        $config = FormAiScoringConfig::where('form_id', $formId)->first();

        if (!$config) {
            return [
                'success' => false,
                'message' => 'No AI scoring configuration found for this form.',
            ];
        }

        $criteria = $config->activeAssessmentCriteria()->with(['formFields', 'contextualFields'])->get();
        if ($criteria->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active assessment criteria found for this form.',
            ];
        }

        $payload = $this->buildPayload($config, $criteria, $answers, $entityId, $entityType);

        try {
            $response = Http::post('https://ai-api-staging.innovation-platform.net/evaluation/evaluate', $payload);

            $responseData = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];

            if (!$response->successful()) {
                // Log failed response
                Log::channel('daily')->error('AI Evaluation Failed', [
                    'form_id' => $formId,
                    'entity_id' => $entityId,
                    'entity_type' => $entityType,
                    'response' => $responseData,
                    'timestamp' => now()->toDateTimeString(),
                ]);

                return [
                    'success' => false,
                    'message' => "Status: {$response->status()} | {$response->body()}",
                ];
            }

            return [
                'success' => true,
                'payload' => $payload,
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            // Log exception
            Log::channel('daily')->error('AI Evaluation Exception', [
                'form_id' => $formId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'exception' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                'timestamp' => now()->toDateTimeString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the payload expected by the AI scoring API.
     */
    public function buildPayload(FormAiScoringConfig $config, $criteria, array $answers, ?int $entityId = null, ?string $entityType = null): array
    {
        return [
            'id' => (string) $config->id,
            // API expects camelCase keys and string values
            'formId' => (string) $config->form_id,
            'ai_prompt' => $config->ai_prompt,
            'total_weight' => $config->total_weight,
            'allocated_weight' => $config->allocated_weight,
            'remaining_weight' => $config->remaining_weight,
            // API requires non-empty strings
            'entityId' => (string) ($entityId ?? '0'),
            'entityType' => (string) ($entityType ?? 'unknown'),
            // API expects "criteria" array
            'criteria' => $criteria->map(function ($criterion) use ($answers) {
                $fields = $criterion->formFields;
                $fieldCount = max(1, $fields->count());
                $fieldWeight = round($criterion->weight / $fieldCount, 1);

                // Get contextual fields
                $contextFields = $criterion->contextualFields;

                return [
                    'criteriaId' => (string) $criterion->id,
                    'name' => $criterion->name,
                    'instruction' => !empty(trim($criterion->instruction ?? '')) ? trim($criterion->instruction) : 'No instruction provided',
                    'maxWeight' => $criterion->weight,
                    'status' => $criterion->status,
                    'sort_order' => $criterion->sort_order,
                    'created_at' => optional($criterion->created_at)?->toDateTimeString(),
                    'updated_at' => optional($criterion->updated_at)?->toDateTimeString(),
                    // Send fields array (mapped fields)
                    'fields' => $fields->map(function ($field) use ($answers, $fieldWeight) {
                        return $this->formatFieldForPayload($field, $answers, $fieldWeight);
                    })->values()->all(),
                    // Send contextual fields array
                    'contextFields' => $contextFields->map(function ($field) use ($answers) {
                        return $this->formatFieldForPayload($field, $answers, null);
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Format a field value for the AI payload.
     */
    protected function formatFieldForPayload($field, array $answers, ?float $maxWeight = null): array
    {
        // API rejects empty field values; provide a sensible fallback
        $rawValue = $answers[$field->slug] ?? null;
        
        // Check if value is truly empty (null, empty string, empty array, false, 0, whitespace only)
        $isEmpty = is_null($rawValue) 
            || $rawValue === '' 
            || (is_array($rawValue) && empty($rawValue))
            || (is_string($rawValue) && trim($rawValue) === '')
            || $rawValue === false;
        
        // Convert to string and use fallback if empty
        if ($isEmpty) {
            $value = 'N/A';
        } else {
            // Convert to string, handling arrays and other types
            if (is_array($rawValue)) {
                $value = implode(', ', array_filter($rawValue, fn($v) => !empty($v))) ?: 'N/A';
            } else {
                $value = (string) $rawValue;
                // If after conversion it's empty, use fallback
                if (trim($value) === '') {
                    $value = 'N/A';
                }
            }
        }

        $fieldData = [
            'id' => $field->id,
            'slug' => $field->slug,
            'type' => $field->type,
            // API requires non-empty value
            'value' => $value,
        ];

        // Only include maxWeight for mapped fields (not contextual fields)
        if ($maxWeight !== null) {
            $fieldData['maxWeight'] = $maxWeight;
        }

        return $fieldData;
    }
}
