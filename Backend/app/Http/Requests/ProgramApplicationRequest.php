<?php

namespace App\Http\Requests;

use App\Models\RegistrationFormConfig;
use App\Models\TeamFormConfig;
use App\Models\Participant;
use App\Rules\QualifiedMemberApplication;
use App\Services\ProgramApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProgramApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (new ProgramApplication())->getMyApplications()->where('program_id', request('program_id'))->isEmpty();
    }

    protected function failedAuthorization(): void
    {
        abort(422, __('program_application.already_submitted'));
    }


    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers', []);

        if (isset($answers['team_serial']) && is_string($answers['team_serial'])) {
            $answers['team_serial'] = array_filter(array_map('trim', explode(',', $answers['team_serial'])));
            $this->merge([
                'answers' => $answers,
            ]);
        }

        // Handle team_logo validation
        if (isset($answers['team_logo'])) {
            $teamLogo = $answers['team_logo'];

            // If it's a file, validate it
            if ($this->hasFile('answers.team_logo')) {
                $file = $this->file('answers.team_logo');
                if (!$file->isValid()) {
                    $this->merge(['team_logo_error' => 'The team logo file is invalid.']);
                } elseif (!in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    $this->merge(['team_logo_error' => 'The team logo must be a file of type: jpg, jpeg, png.']);
                } elseif ($file->getSize() > 2048 * 1024) { // 2MB
                    $this->merge(['team_logo_error' => 'The team logo must not be greater than 2MB.']);
                }
            }
            // If it's a string (existing path), validate it's a valid path
            elseif (is_string($teamLogo) && !empty($teamLogo)) {
                if (!str_starts_with($teamLogo, 'uploads/')) {
                    $this->merge(['team_logo_error' => 'Invalid team logo path.']);
                }
            }
        }
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
                if (in_array($key, ['has_team', 'team_serial', 'track', 'sub_track', 'file', 'team_logo'])) {
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
                                    __('program_application.mandatory_checkbox_options_required', [
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
                                    __('program_application.The field :field must be at least :min characters.', ['field' => $fieldLabel, 'min' => $minLength])
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
                                    __('program_application.The field :field must not exceed :max characters.', ['field' => $fieldLabel, 'max' => $maxLength])
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
                                __('program_application.The field :field must not exceed :max characters.', ['field' => $fieldLabel, 'max' => $maxLength])
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
                            __('program_application.The field :field must not contain more than :max items.', ['field' => $fieldLabel, 'max' => $maxArraySize])
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
                                    __('program_application.The field :field must not exceed :max characters.', ['field' => $fieldLabel . '.' . $nestedKey, 'max' => $maxInputLength])
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
                    __('program_application.The total size of all form fields must not exceed 1MB. Please reduce the length of your input.')
                );
            }

            $programId = $this->input('program_id');
            $hasTeam = $this->input('answers.has_team');

            if (!$programId) {
                return;
            }

            // Get the registration configuration for this program
            $registrationConfig = RegistrationFormConfig::where('program_id', $programId)
                ->where('is_active', true)
                ->where('is_archived', false)
                ->first();

            if (!$registrationConfig) {
                return;
            }

            // Enforce team requirement based on registration type
            if ($registrationConfig->registration_type === 'team' && !$hasTeam) {
                $validator->errors()->add(
                    'answers.has_team',
                    __('program_application.This program only allows team registration. You must register as a team.')
                );
            }

            // Enforce individual requirement based on registration type
            if ($registrationConfig->registration_type === 'individual' && $hasTeam) {
                $validator->errors()->add(
                    'answers.has_team',
                    __('program_application.This program only allows individual registration. Team registration is not allowed.')
                );
            }

            // Validate team serial numbers when team registration is enabled (team or both)
            if (in_array($registrationConfig->registration_type, ['team', 'both']) && $hasTeam) {
                $teamSerialNumbers = $this->input('answers.team_serial', []);

                // Normalize teamSerialNumbers
                if (is_string($teamSerialNumbers)) {
                    $teamSerialNumbers = array_filter(array_map('trim', explode(',', $teamSerialNumbers)));
                } elseif (!is_array($teamSerialNumbers)) {
                    $teamSerialNumbers = [];
                }
                $teamSerialNumbers = array_unique(array_filter($teamSerialNumbers));

                // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
                // RegistrationFormConfig is the source of truth for team size limits
                $teamFormConfig = TeamFormConfig::where('program_id', $programId)
                    ->active()
                    ->notArchived()
                    ->first();
                
                // If RegistrationFormConfig exists, use it (it's the source of truth)
                if ($registrationConfig) {
                    // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
                    $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                    $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
                } else {
                    // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
                    if ($teamFormConfig) {
                        // Use TeamFormConfig values - don't use ?? operator if value is explicitly null
                        $minTeamMembers = $teamFormConfig->min_team_members !== null ? $teamFormConfig->min_team_members : 2;
                        $maxTeamMembers = $teamFormConfig->max_team_members !== null ? $teamFormConfig->max_team_members : config('team.max_members', 6);
                    } else {
                        // Use defaults if neither exists
                        $minTeamMembers = 2;
                        $maxTeamMembers = config('team.max_members', 6);
                    }
                }

                // Calculate total: new members + leader (1)
                // Filter out the current user from serial numbers
                $participantIds = \App\Models\Participant::whereIn('serial_number', $teamSerialNumbers)
                    ->pluck('id')
                    ->toArray();
                $participantIds = array_diff($participantIds, [auth()->id()]);
                $newMembersCount = count($participantIds);
                $totalMembers = $newMembersCount + 1; // Leader is always counted

                // Validate MINIMUM team size - reject if empty or below minimum
                if ($totalMembers < $minTeamMembers) {
                    $validator->errors()->add(
                        'answers.team_serial',
                        __('program_application.The total number of team members must be at least :min.', ['min' => $minTeamMembers])
                            ?: "The total number of team members must be at least {$minTeamMembers}."
                    );
                }

                // Validate MAXIMUM team size
                if ($totalMembers > $maxTeamMembers) {
                    $validator->errors()->add(
                        'answers.team_serial',
                        __('program_application.The total number of team members must not exceed :max.', ['max' => $maxTeamMembers])
                    );
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        //$customErrors = [];
        $customErrors = [];

        foreach ($errors as $key => $messages) {
            if (str_starts_with($key, 'answers.team_serial.')) {
                //$customErrors['team_serial'] = array_merge($customErrors['team_serial'] ?? [], $messages);
                $customErrors['team_serial'] = $messages[0];
            } elseif (str_starts_with($key, 'answers.')) {
                // Remove "answers." prefix and use first message as string
                $fieldKey = substr($key, 8); // Remove "answers." prefix (8 characters)
                $customErrors[$fieldKey] = is_array($messages) ? $messages[0] : $messages;
            } else {
                $customErrors[$key] = $messages;
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => isset($customErrors['team_serial']) ? $customErrors['team_serial'] : 'Validation failed.',
            'errors' => $customErrors,
        ], 422));
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'exists:programs,id'],
            'form_id' => ['required', 'exists:forms,id'],
            'type' => ['required', 'string', 'in:submission,draft'],
            'answers' => ['required', 'array'],


            'answers.team_serial' => ['max:8'],
            'answers.team_serial.*' => [
                'string',
                'distinct',
                // 'exists:participants,serial_number',
                new QualifiedMemberApplication($this->input('answers.has_team')),
            ],

            'answers.has_team' => ['required', 'boolean'],
            'answers.register_as' => ['nullable', 'string', 'max:255'],
            'answers.team_name' => ['nullable', 'string', 'max:255'],
            'answers.file' => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:2048'],
            'answers.team_logo' => ['nullable'],
            'answers.track' => ['nullable', 'integer', 'exists:tracks,id'],
            'answers.sub_track' => ['nullable', 'integer', 'exists:sub_tracks,id'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Step 1 Errors
            'answers.team_logo.image' => __('program_application.The team logo must be a valid image.'),
            'answers.team_logo.max' => __('program_application.Image size must not exceed 1MB.'),

            'answers.team_strength.required_if' => __('program_application.Please describe your team\'s strengths.'),
            'answers.team_strength.max' => __('program_application.The maximum message limit is 500 characters.'),

            'answers.team_serial.min' => __('program_application.At least one member must be added to the team.'),
            'answers.team_serial.required_if' => __('program_application.At least one member must be added to the team.'),
            'answers.team_serial.max' => __('program_application.The maximum number of team members is 6.'),

            // Step 2 Errors
            'answers.track_id.exists' => __('program_application.Please select a valid Track.'),
            'answers.sub_track_id.exists' => __('program_application.Please select a valid challenge.'),
        ];

    }
}
