<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow drafts for all team members
        if ($this->input('type') === 'draft') {
            return true;
        }

        // For submissions, check if user is team leader
        if ($this->input('type') === 'submission') {
            $applicationId = $this->input('application_id');
            
            if (!$applicationId) {
                return false;
            }

            // Get the application
            $application = \App\Models\CompetitionApplication::find($applicationId);
            
            if (!$application) {
                return false;
            }

            // If no team, allow submission (individual application)
            if (!$application->has_team) {
                return true;
            }

            // Get the team
            $team = \App\Models\Team::where('application_id', $applicationId)->first();
            
            if (!$team) {
                return false;
            }

            // Check if the current user is the team leader
            $isTeamLeader = $team->members()
                ->where('participant_id', auth()->id())
                ->where('is_leader', true)
                ->exists();

            return $isTeamLeader;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['required', 'exists:competition_applications,id'],
            'form_id' => ['required', 'exists:forms,id'],
            'answers' => ['required', 'array'],
            'type' => ['required', 'string', 'in:draft,submission'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate all string values in answers array have reasonable length limits
            // This prevents DoS attacks from extremely long input values
            $answers = $this->input('answers', []);
            $formId = $this->input('form_id');
            
            // Maximum allowed length for text fields (5000 characters)
            $maxTextLength = 5000;
            // Maximum allowed length for regular input fields (1000 characters)
            $maxInputLength = 1000;
            // Maximum array size
            $maxArraySize = 100;
            
            // Calculate total payload size for DoS prevention
            $totalSize = 0;
            
            // Get form fields with their validation rules if form_id is provided
            $formFields = [];
            if ($formId) {
                $form = \App\Models\Form::with('fields')->find($formId);
                if ($form) {
                    $formFields = $form->fields->keyBy('slug')->toArray();
                }
            }
            
            foreach ($answers as $key => $value) {
                // Skip known fields that have their own validation
                if (in_array($key, ['file', 'project_name'])) {
                    continue;
                }
                
                // Get field configuration if available
                $fieldConfig = $formFields[$key] ?? null;
                $fieldValidationRules = $fieldConfig['validation_rules'] ?? [];
                $fieldType = $fieldConfig['type'] ?? null;
                $fieldRequired = $fieldConfig['required'] ?? false;

                if ($fieldType === 'checkbox' && !empty($fieldConfig['mandatory_options'])) {
                    if ($this->input('type') === 'submission') {
                        $field = \App\Models\FormField::find($fieldConfig['id'] ?? null);
                        if ($field) {
                            $uncheckedMandatory = $field->validateMandatoryOptions($value);
                            if (!empty($uncheckedMandatory)) {
                                $locale = request()->header('Accept-Language', 'en');
                                $locale = in_array($locale, ['ar', 'en']) ? $locale : 'en';
                                
                                $uncheckedLabels = $field->getUncheckedMandatoryLabels($uncheckedMandatory, $locale);
                                $fieldLabel = is_array($fieldConfig['label'] ?? null)
                                    ? ($fieldConfig['label'][$locale] ?? $fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                                    : ($fieldConfig['label'] ?? $key);
                                
                                $validator->errors()->add(
                                    "answers.{$key}",
                                    __('competition_application.mandatory_checkbox_options_required', [
                                        'field' => $fieldLabel,
                                        'options' => implode(', ', $uncheckedLabels)
                                    ])
                                );
                            }
                        }
                    }
                }
                
                // Validate string values
                if (is_string($value)) {
                    // Calculate size contribution
                    $totalSize += strlen($value);
                    
                    // Apply dynamic validation rules from form field
                    foreach ($fieldValidationRules as $rule) {
                        $ruleName = $rule['rule'] ?? null;
                        $ruleValue = $rule['value'] ?? null;
                        
                        if ($ruleName === 'min' && $ruleValue !== null) {
                            $minLength = (int) $ruleValue;
                            // Use mb_strlen for proper multibyte character counting (Arabic, etc.)
                            if (mb_strlen($value, 'UTF-8') < $minLength) {
                                $fieldLabel = is_array($fieldConfig['label'] ?? null) 
                                    ? ($fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                                    : ($fieldConfig['label'] ?? $key);
                                $validator->errors()->add(
                                    "answers.{$key}",
                                    __('project.The field :field must be at least :min characters.', ['field' => $fieldLabel, 'min' => $minLength])
                                );
                            }
                        }
                        
                        if ($ruleName === 'max' && $ruleValue !== null) {
                            $maxLength = (int) $ruleValue;
                            // Use mb_strlen for proper multibyte character counting (Arabic, etc.)
                            if (mb_strlen($value, 'UTF-8') > $maxLength) {
                                $fieldLabel = is_array($fieldConfig['label'] ?? null) 
                                    ? ($fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                                    : ($fieldConfig['label'] ?? $key);
                                $validator->errors()->add(
                                    "answers.{$key}",
                                    __('project.The field :field must not exceed :max characters.', ['field' => $fieldLabel, 'max' => $maxLength])
                                );
                            }
                        }
                    }
                    
                    // Apply default max length based on field type if no max rule is set
                    if (empty(array_filter($fieldValidationRules, fn($r) => ($r['rule'] ?? null) === 'max'))) {
                        // Determine max length based on field type
                        // Text areas can be longer, regular inputs shorter
                        $maxLength = ($fieldType === 'textarea' || 
                                     str_contains($key, 'comment') || 
                                     str_contains($key, 'description') || 
                                     str_contains($key, 'text') ||
                                     str_contains($key, 'message')) 
                                     ? $maxTextLength 
                                     : $maxInputLength;
                        
                        // Use mb_strlen for proper multibyte character counting (Arabic, etc.)
                        if (mb_strlen($value, 'UTF-8') > $maxLength) {
                            $fieldLabel = is_array($fieldConfig['label'] ?? null) 
                                ? ($fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                                : ($fieldConfig['label'] ?? $key);
                            $validator->errors()->add(
                                "answers.{$key}",
                                __('project.The field :field must not exceed :max characters.', ['field' => $fieldLabel, 'max' => $maxLength])
                            );
                        }
                    }
                }
                
                // Validate array values
                if (is_array($value)) {
                    $totalSize += count($value) * 100; // Estimate array overhead
                    
                    if (count($value) > $maxArraySize) {
                        $fieldLabel = is_array($fieldConfig['label'] ?? null) 
                            ? ($fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                            : ($fieldConfig['label'] ?? $key);
                        $validator->errors()->add(
                            "answers.{$key}",
                            __('project.The field :field must not contain more than :max items.', ['field' => $fieldLabel, 'max' => $maxArraySize])
                        );
                    }
                    
                    // Recursively validate nested string values
                    foreach ($value as $nestedKey => $nestedValue) {
                        if (is_string($nestedValue)) {
                            $totalSize += strlen($nestedValue); // Keep strlen for byte size calculation (DoS protection)
                            // Use mb_strlen for character count validation (Arabic, etc.)
                            if (mb_strlen($nestedValue, 'UTF-8') > $maxInputLength) {
                                $fieldLabel = is_array($fieldConfig['label'] ?? null) 
                                    ? ($fieldConfig['label']['en'] ?? $fieldConfig['label']['ar'] ?? $key)
                                    : ($fieldConfig['label'] ?? $key);
                                $validator->errors()->add(
                                    "answers.{$key}.{$nestedKey}",
                                    __('project.The field :field must not exceed :max characters.', ['field' => $fieldLabel . '.' . $nestedKey, 'max' => $maxInputLength])
                                );
                            }
                        }
                    }
                }
            }
            
            // Validate total payload size to prevent DoS attacks
            // Maximum total size: 1MB (1,048,576 bytes) for all answers combined
            $maxTotalSize = 1048576;
            if ($totalSize > $maxTotalSize) {
                $validator->errors()->add(
                    'answers',
                    __('project.The total size of all form fields must not exceed 1MB. Please reduce the length of your input.')
                );
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => __('project.The type field must be either draft or submission'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        $customErrors = [];

        foreach ($errors as $key => $messages) {
            if (str_starts_with($key, 'answers.')) {
                // Remove "answers." prefix and use first message as string
                $fieldKey = substr($key, 8); // Remove "answers." prefix (8 characters)
                $customErrors[$fieldKey] = is_array($messages) ? $messages[0] : $messages;
            } else {
                $customErrors[$key] = is_array($messages) ? $messages[0] : $messages;
            }
        }

        throw new HttpResponseException(response()->json([
            'errors' => $customErrors,
        ], 422));
    }
}
