<?php

namespace App\Services;

use App\Models\FormAiEnhancementConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEnhancementService
{
    /**
     * Enhance form field values using AI.
     */
    public function enhance(int $formId, array $formData): array
    {
        $config = FormAiEnhancementConfig::where('form_id', $formId)->first();

        if (!$config) {
            return [
                'success' => false,
                'message' => 'No AI enhancement configuration found for this form.',
            ];
        }

        if (!$config->isEnhancementEnabled()) {
            return [
                'success' => false,
                'message' => 'AI enhancement is not enabled for this form.',
            ];
        }

        // Get form fields to determine which ones to enhance
        $form = $config->form;
        if (!$form) {
            return [
                'success' => false,
                'message' => 'Form not found.',
            ];
        }

        // Load fields if not already loaded
        if (!$form->relationLoaded('fields')) {
            $form->load('fields');
        }

        // Filter fields that should be enhanced
        $enhanceableFields = $form->fields()
            ->whereIn('type', ['text', 'textarea', 'email', 'url'])
            ->get()
            ->filter(function ($field) use ($config) {
                return $config->shouldEnhanceField($field->slug);
            });

        if ($enhanceableFields->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No fields available for enhancement.',
            ];
        }

        // Build payload with only the fields to enhance
        $payload = $this->buildPayload($config, $enhanceableFields, $formData);

        try {
            $response = Http::timeout(60)->post('https://ai-api-staging.innovation-platform.net/evaluation/enhance', $payload);

            $responseData = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];

            if (!$response->successful()) {
                // Log failed response
                Log::channel('daily')->error('AI Enhancement Failed', [
                    'form_id' => $formId,
                    'response' => $responseData,
                    'timestamp' => now()->toDateTimeString(),
                ]);

                return [
                    'success' => false,
                    'message' => "AI service unavailable. Try again.",
                ];
            }

            $responseJson = $response->json();
            
            // Extract suggestions from response
            $suggestions = $this->extractSuggestions($responseJson, $enhanceableFields);

            return [
                'success' => true,
                'suggestions' => $suggestions,
                'response' => $responseJson,
            ];
        } catch (\Throwable $e) {
            // Log exception
            Log::channel('daily')->error('AI Enhancement Exception', [
                'form_id' => $formId,
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
                'message' => 'AI service unavailable. Try again.',
            ];
        }
    }

    /**
     * Build the payload expected by the AI enhancement API.
     */
    protected function buildPayload(FormAiEnhancementConfig $config, $fields, array $formData): array
    {
        // Build field data with current values and instructions
        $fieldData = $fields->map(function ($field) use ($formData, $config) {
            $value = $formData[$field->slug] ?? '';

            // Convert to string, handling arrays
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, fn($v) => !empty($v))) ?: '';
            } else {
                $value = (string) $value;
            }

            $label = is_array($field->label) 
                ? ($field->label['en'] ?? reset($field->label)) 
                : $field->label;

            // Get field-specific instructions and context field slug
            $fieldInstructions = $config->getFieldInstructions($field->slug);
            $contextFieldSlug = $config->getFieldContext($field->slug);

            $fieldPayload = [
                'fieldId' => (string) $field->id,
                'slug' => $field->slug,
                'label' => $label,
                'type' => $field->type,
                // API expects "value" key, not "currentValue"
                'value' => $value,
            ];

            // Add instructions if available
            if ($fieldInstructions) {
                $fieldPayload['instructions'] = $fieldInstructions;
            }

            // Add context value if context field is specified
            if ($contextFieldSlug && isset($formData[$contextFieldSlug])) {
                $contextValue = $formData[$contextFieldSlug];
                // Convert to string, handling arrays
                if (is_array($contextValue)) {
                    $contextValue = implode(', ', array_filter($contextValue, fn($v) => !empty($v))) ?: '';
                } else {
                    $contextValue = (string) $contextValue;
                }
                $fieldPayload['context'] = $contextValue;
            }

            // Ensure context is always a non-empty string as required by the AI API
            if (!isset($fieldPayload['context']) || $fieldPayload['context'] === null || $fieldPayload['context'] === '') {
                // Fallback to the field value, or label if value is empty
                $fallbackContext = $value !== '' ? $value : (string) $label;
                $fieldPayload['context'] = $fallbackContext;
            } elseif (is_array($fieldPayload['context'])) {
                // Normalize any array context to a comma-separated string
                $fieldPayload['context'] = implode(', ', array_filter($fieldPayload['context'], fn($v) => !empty($v))) ?: ((string) $label);
            }

            return $fieldPayload;
        })->values()->all();

        return [
            'formId' => (string) $config->form_id,
            'fields' => $fieldData,
        ];
    }

    /**
     * Extract suggestions from API response.
     */
    protected function extractSuggestions(array $responseJson, $fields): array
    {
        $suggestions = [];

        // Expected response format: { "data": { "suggestions": [ { "fieldSlug": "...", "suggestedValue": "..." } ] } }
        $responseSuggestions = data_get($responseJson, 'data.suggestions', []);

        foreach ($responseSuggestions as $suggestion) {
            $fieldSlug = $suggestion['fieldSlug'] ?? $suggestion['slug'] ?? null;
            $suggestedValue = $suggestion['suggestedValue'] ?? $suggestion['value'] ?? null;

            if ($fieldSlug && $suggestedValue !== null) {
                $field = $fields->firstWhere('slug', $fieldSlug);
                if ($field) {
                    $label = is_array($field->label) 
                        ? ($field->label['en'] ?? reset($field->label)) 
                        : $field->label;

                    $suggestions[] = [
                        'fieldSlug' => $fieldSlug,
                        'fieldLabel' => $label,
                        'currentValue' => $suggestion['currentValue'] ?? '',
                        'suggestedValue' => $suggestedValue,
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Enhance form data using AI with direct payload (for when frontend sends the payload directly).
     */
    public function enhanceWithPayload(array $payload): array
    {
        $formId = (int) ($payload['formId'] ?? 0);

        if (!$formId) {
            return [
                'success' => false,
                'message' => 'Form ID is required.',
            ];
        }

        $config = FormAiEnhancementConfig::where('form_id', $formId)->first();

        if (!$config) {
            return [
                'success' => false,
                'message' => 'No AI configuration found for this form.',
            ];
        }

        if (!$config->isEnhancementEnabled()) {
            return [
                'success' => false,
                'message' => 'AI enhancement is not enabled for this form.',
            ];
        }

        // Normalize fields payload to always include a non-empty string "context" as required by the AI API
        if (isset($payload['fields']) && is_array($payload['fields'])) {
            $payload['fields'] = array_map(function ($field) {
                if (!is_array($field)) {
                    return $field;
                }

                $value = $field['value'] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', array_filter($value, fn($v) => !empty($v))) ?: '';
                } else {
                    $value = (string) $value;
                }

                // Normalize existing context
                if (isset($field['context'])) {
                    if (is_array($field['context'])) {
                        $field['context'] = implode(', ', array_filter($field['context'], fn($v) => !empty($v))) ?: '';
                    } elseif ($field['context'] !== null) {
                        $field['context'] = (string) $field['context'];
                    }
                }

                if (!isset($field['context']) || $field['context'] === null || $field['context'] === '') {
                    // Fallback to the field value, or label if value is empty
                    $fallbackContext = $value !== '' ? $value : (string) ($field['label'] ?? '');
                    $field['context'] = $fallbackContext;
                }

                return $field;
            }, $payload['fields']);
        }

        try {
            $response = Http::timeout(60)->post('https://ai-api-staging.innovation-platform.net/evaluation/enhance', $payload);

            $responseData = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];

            if (!$response->successful()) {
                // Log failed response with payload for debugging
                // Sanitize payload for logging (limit field values to prevent huge logs)
                $sanitizedPayload = $payload;
                if (isset($sanitizedPayload['fields']) && is_array($sanitizedPayload['fields'])) {
                    $sanitizedPayload['fields'] = array_map(function ($field) {
                        if (isset($field['value']) && is_string($field['value']) && strlen($field['value']) > 200) {
                            $field['value'] = substr($field['value'], 0, 200) . '... (truncated)';
                        }
                        if (isset($field['context']) && is_string($field['context']) && strlen($field['context']) > 200) {
                            $field['context'] = substr($field['context'], 0, 200) . '... (truncated)';
                        }
                        return $field;
                    }, $sanitizedPayload['fields']);
                }
                
                Log::channel('daily')->error('AI Enhancement Failed', [
                    'form_id' => $formId,
                    'payload' => $sanitizedPayload,
                    'response' => $responseData,
                    'timestamp' => now()->toDateTimeString(),
                ]);

                return [
                    'success' => false,
                    'message' => "AI service unavailable. Try again.",
                    'status' => $response->status(),
                ];
            }

            $responseJson = $response->json();
            
            // Extract suggestions from response - try multiple possible structures
            $suggestions = [];
            
            // Try different possible response structures
            $responseSuggestions = null;
            
            // Structure 1: { "data": { "suggestions": [...] } }
            if (isset($responseJson['data']['suggestions']) && is_array($responseJson['data']['suggestions'])) {
                $responseSuggestions = $responseJson['data']['suggestions'];
            }
            // Structure 2: { "suggestions": [...] }
            elseif (isset($responseJson['suggestions']) && is_array($responseJson['suggestions'])) {
                $responseSuggestions = $responseJson['suggestions'];
            }
            // Structure 3: { "data": { "fields": [ { "slug": "...", "suggestedValue": "..." } ] } }
            elseif (isset($responseJson['data']['fields']) && is_array($responseJson['data']['fields'])) {
                $responseSuggestions = $responseJson['data']['fields'];
            }
            // Structure 4: { "fields": [ { "slug": "...", "suggestedValue": "..." } ] }
            elseif (isset($responseJson['fields']) && is_array($responseJson['fields'])) {
                $responseSuggestions = $responseJson['fields'];
            }
            // Structure 5: { "data": [...] } (suggestions directly in data)
            elseif (isset($responseJson['data']) && is_array($responseJson['data']) && !isset($responseJson['data']['suggestions'])) {
                // Check if data is an array of suggestions
                if (isset($responseJson['data'][0]) && is_array($responseJson['data'][0])) {
                    $responseSuggestions = $responseJson['data'];
                }
            }
            // Structure 6: Direct array of suggestions
            elseif (is_array($responseJson) && isset($responseJson[0]) && is_array($responseJson[0])) {
                $responseSuggestions = $responseJson;
            }
            
            // Default: try data_get as fallback
            if ($responseSuggestions === null) {
                $responseSuggestions = data_get($responseJson, 'data.suggestions', []) 
                    ?? data_get($responseJson, 'suggestions', [])
                    ?? data_get($responseJson, 'data.fields', [])
                    ?? data_get($responseJson, 'fields', [])
                    ?? data_get($responseJson, 'data', []);
            }

            if (is_array($responseSuggestions)) {
                foreach ($responseSuggestions as $suggestion) {
                    if (!is_array($suggestion)) {
                        continue;
                    }
                    
                    // Try multiple possible keys for field identifier
                    $fieldSlug = $suggestion['fieldSlug'] 
                        ?? $suggestion['slug'] 
                        ?? $suggestion['field_id']
                        ?? $suggestion['fieldId']
                        ?? null;
                    
                    // If no slug found, skip this suggestion
                    if (!$fieldSlug) {
                        continue;
                    }
                    
                    // For AI API response structure: { "data": { "suggestions": [ { "fieldSlug": "...", "suggestedValue": "..." } ] } }
                    // Priority: suggestedValue > suggested_value > enhancedValue > enhanced_value > value
                    $suggestedValue = null;
                    
                    // First, check for explicit suggestedValue keys (this is what AI API returns)
                    if (isset($suggestion['suggestedValue']) && $suggestion['suggestedValue'] !== null && $suggestion['suggestedValue'] !== '') {
                        $suggestedValue = $suggestion['suggestedValue'];
                    } elseif (isset($suggestion['suggested_value']) && $suggestion['suggested_value'] !== null && $suggestion['suggested_value'] !== '') {
                        $suggestedValue = $suggestion['suggested_value'];
                    } elseif (isset($suggestion['enhancedValue']) && $suggestion['enhancedValue'] !== null && $suggestion['enhancedValue'] !== '') {
                        $suggestedValue = $suggestion['enhancedValue'];
                    } elseif (isset($suggestion['enhanced_value']) && $suggestion['enhanced_value'] !== null && $suggestion['enhanced_value'] !== '') {
                        $suggestedValue = $suggestion['enhanced_value'];
                    } 
                    // If no explicit suggestedValue, check if 'value' exists (fallback for other structures)
                    elseif (isset($suggestion['value']) && $suggestion['value'] !== null && $suggestion['value'] !== '') {
                        // This is likely a field object where 'value' contains the suggested/enhanced value from AI
                        $suggestedValue = $suggestion['value'];
                    }

                    if ($suggestedValue !== null && $suggestedValue !== '') {
                        $suggestions[] = [
                            'fieldSlug' => $fieldSlug,
                            'suggestedValue' => $suggestedValue,
                        ];
                    }
                }
            }


            return [
                'success' => true,
                'suggestions' => $suggestions,
                'response' => $responseJson,
            ];
        } catch (\Throwable $e) {
            // Log exception with payload for debugging
            // Sanitize payload for logging (limit field values to prevent huge logs)
            $sanitizedPayload = $payload;
            if (isset($sanitizedPayload['fields']) && is_array($sanitizedPayload['fields'])) {
                $sanitizedPayload['fields'] = array_map(function ($field) {
                    if (isset($field['value']) && is_string($field['value']) && strlen($field['value']) > 200) {
                        $field['value'] = substr($field['value'], 0, 200) . '... (truncated)';
                    }
                    if (isset($field['context']) && is_string($field['context']) && strlen($field['context']) > 200) {
                        $field['context'] = substr($field['context'], 0, 200) . '... (truncated)';
                    }
                    return $field;
                }, $sanitizedPayload['fields']);
            }
            
            Log::channel('daily')->error('AI Enhancement Exception', [
                'form_id' => $formId,
                'payload' => $sanitizedPayload,
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
                'message' => 'AI service unavailable. Try again.',
            ];
        }
    }
}

