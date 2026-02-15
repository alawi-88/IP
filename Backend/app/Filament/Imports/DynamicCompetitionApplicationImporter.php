<?php

namespace App\Filament\Imports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Country;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Nationality;
use App\Models\Participant;
use App\Models\Track;
use App\Models\SubTrack;
use App\Models\City;
use App\Notifications\CompetitionRegistration;
use App\Exceptions\ImportValidationException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use Throwable;

class DynamicCompetitionApplicationImporter extends Importer
{
    protected static ?string $model = CompetitionApplication::class;

    protected array $skippedFields = [];

    /**
     * Wrap import in try-catch so any uncaught exception is reported as a validation
     * error in the failed rows CSV instead of the generic "System error, please contact support."
     */
    public function __invoke(array $data): void
    {
        try {
            parent::__invoke($data);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ImportValidationException::withMessages([
                'email' => $e->getMessage(),
            ]);
        }
    }

    public static function getColumns(): array
    {
        // Get only current competition if it's open
        $currentCompetitionId = session('current_competition_id');

        if (!$currentCompetitionId) {
            // If no current competition, return empty examples
            $competitionIds = [];
            $formIdsArray = [];
            $openCompetitions = collect();
        } else {
            // Get current competition only if it's open
            $currentCompetition = Competition::published()
                ->active()
                ->where('id', $currentCompetitionId)
                ->whereHas('stages', function ($q) {
                    $q->where('slug', 'registration')
                      ->where('ends_at', '>', now());
                })
                ->first();

            if ($currentCompetition) {
                // Get the form for this competition
                $form = Form::where('competition_id', $currentCompetitionId)
                    ->registrationType()
                    ->published()
                    ->active()
                    ->first();

                if ($form) {
                    $competitionIds = [(string) $currentCompetitionId];
                    $formIdsArray = [(string) $form->id];
                    $openCompetitions = collect([$currentCompetition]);
                } else {
                    $competitionIds = [];
                    $formIdsArray = [];
                    $openCompetitions = collect();
                }
            } else {
                $competitionIds = [];
                $formIdsArray = [];
                $openCompetitions = collect();
            }
        }

        // Get Track and Subtrack examples for the current competition only
        $trackExamples = [];
        $subTrackExamples = [];

        if ($currentCompetitionId) {
            // Get tracks for the current competition
            $tracks = Track::where('competition_id', $currentCompetitionId)
                ->orderBy('order')
                ->get();

            // Add one example per track (prefer slug, fallback to ID)
            // Limit to first 10 tracks to avoid too many examples
            foreach ($tracks->take(10) as $track) {
                if ($track->slug) {
                    $trackExamples[] = $track->slug;
                } else {
                    $trackExamples[] = (string) $track->id;
                }
            }

            // Get subtracks for the current competition
            $subTracks = SubTrack::whereHas('track', function ($query) use ($currentCompetitionId) {
                    $query->where('competition_id', $currentCompetitionId);
                })
                ->orderBy('order')
                ->get();

            // Add one example per subtrack (prefer slug, fallback to ID)
            // Limit to first 10 subtracks to avoid too many examples
            foreach ($subTracks->take(10) as $subTrack) {
                if ($subTrack->slug) {
                    $subTrackExamples[] = $subTrack->slug;
                } else {
                    $subTrackExamples[] = (string) $subTrack->id;
                }
            }

            // Remove duplicates while preserving order
            $trackExamples = array_values(array_unique($trackExamples));
            $subTrackExamples = array_values(array_unique($subTrackExamples));
        }

        $columns = [
            ImportColumn::make('competition_id')
                ->label('Competition ID')
                ->requiredMapping()
                ->examples($competitionIds ?: ['1', '2', '3'])
                ->rules(['required', 'integer', 'exists:competitions,id']),

            ImportColumn::make('form_id')
                ->label('Form ID')
                ->requiredMapping()
                ->examples($formIdsArray ?: ['1', '2', '3'])
                ->rules(['required', 'integer', 'exists:forms,id']),

            ImportColumn::make('email')
                ->label('Participant Email')
                ->requiredMapping()
                ->rules(['required', 'email', function ($attribute, $value, $fail) {
                    // Participant will be created automatically if not found
                    // Validation will be done in resolveRecord
                }]),

            ImportColumn::make('participant_name')
                ->label('Participant Name')
                ->examples(['John Doe', 'Jane Smith'])
                ->rules(['nullable', 'string', 'max:255']),
        ];

        // Collect all unique form fields from all open competitions
        $allFormFields = collect();

        foreach ($openCompetitions as $competition) {
            $form = Form::where('competition_id', $competition->id)
                ->registrationType()
                ->published()
                ->active()
                ->first();

            if ($form) {
                $fields = $form->fields()
                    ->whereNotIn('type', ['section_header', 'paragraph']) // Skip display-only fields
                    ->orderBy('sort')
                    ->get();

                foreach ($fields as $field) {
                    // Use slug as key to avoid duplicates
                    if (!$allFormFields->has($field->slug)) {
                        $allFormFields->put($field->slug, $field);
                    }
                }
            }
        }

        // Check if track and sub_track exist in form fields
        $hasTrackField = $allFormFields->has('track');
        $hasSubTrackField = $allFormFields->has('sub_track');

        // Check if competition has tracks/sub_tracks (even if not in form)
        $competitionHasTracks = !empty($trackExamples);
        $competitionHasSubTracks = !empty($subTrackExamples);

        // Add track and sub_track columns if:
        // 1. They exist in the form fields, OR
        // 2. The competition has tracks/sub_tracks
        if (($hasTrackField || $competitionHasTracks) && !$hasTrackField) {
            // Add track column if competition has tracks but not in form
            $columns[] = ImportColumn::make('track')
                ->label('Track')
                ->examples($trackExamples)
                ->rules(['nullable']);
        }

        if (($hasSubTrackField || $competitionHasSubTracks) && !$hasSubTrackField) {
            // Add sub_track column if competition has sub_tracks but not in form
            $columns[] = ImportColumn::make('sub_track')
                ->label('Sub-Track')
                ->examples($subTrackExamples)
                ->rules(['nullable']);
        }

        // Add form fields as import columns
        // Exclude fields that conflict with required columns
        $excludedSlugs = ['email', 'competition_id', 'form_id', 'participant_name'];

        // If track/sub_track are in form fields, they'll be added automatically
        // If they're not in form but competition has them, we already added them above
        if ($hasTrackField) {
            $excludedSlugs[] = 'track';
        }
        if ($hasSubTrackField) {
            $excludedSlugs[] = 'sub_track';
        }

        foreach ($allFormFields as $slug => $field) {
            // Skip if slug conflicts with required columns
            if (in_array($slug, $excludedSlugs)) {
                continue;
            }

            $label = is_array($field->label)
                ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                : ($field->label ?? $slug);

            $column = ImportColumn::make($slug)
                ->label($label)
                // If field has conditional logic, make it nullable at column level
                // because conditional logic will be evaluated later in validateFormFields
                // where required validation will happen only if the condition is met
                ->rules(($field->required && !($field->conditional_logic && $field->conditional_logic_rules)) ? ['sometimes'] : ['nullable']);

            // Add special examples for track and sub_track from database
            if ($slug === 'track' && !empty($trackExamples)) {
                $column->examples($trackExamples);
            } elseif ($slug === 'sub_track' && !empty($subTrackExamples)) {
                $column->examples($subTrackExamples);
            }

            // Add examples based on field type
            switch ($field->type) {
                case 'dropdown':
                case 'radio':
                case 'rating':
                    $examples = static::extractFieldOptions($field);
                    if (!empty($examples)) {
                        $column->examples($examples);
                    }
                    break;

                case 'checkbox':
                case 'multi_select':
                    $examples = static::extractFieldOptions($field);
                    if (!empty($examples)) {
                        // For multi-select and checkbox, show examples with note about comma-separated
                        $column->examples($examples);
                    }
                    break;

                case 'number':
                    $column->examples(['100', '50', '0']);
                    break;

                case 'date':
                    $column->examples(['2024-01-01', '2024-12-31']);
                    break;

                case 'time':
                    $column->examples(['09:00', '14:30', '18:45']);
                    break;

                case 'email':
                    $column->examples(['example@email.com']);
                    break;

                case 'phone':
                    $column->examples(['+966501234567', '0501234567']);
                    break;

                case 'url':
                    $column->examples(['https://example.com']);
                    break;
            }

            $columns[] = $column;
        }

        return $columns;
    }

    public function resolveRecord(): ?CompetitionApplication
    {
        CompetitionApplication::flushEventListeners();

        $competitionId = $this->data['competition_id'] ?? null;
        $formId = $this->data['form_id'] ?? null;
        $email = $this->data['email'] ?? null;

        if (!$competitionId || !$email) {
            return null;
        }

        // Get the registration form - use form_id if provided, otherwise find by competition_id
        if ($formId) {
            $form = Form::where('id', $formId)
                ->where('competition_id', $competitionId)
                ->registrationType()
                ->published()
                ->active()
                ->first();
        } else {
            $form = Form::where('competition_id', $competitionId)
                ->registrationType()
                ->published()
                ->active()
                ->first();
        }

        if (!$form) {
            // Throw ImportValidationException to mark this row as failed
            // This will be handled by Filament and stored in failed rows with the error message
            throw ImportValidationException::withMessages([
                'form_id' => "No registration form found for competition ID: {$competitionId}" . ($formId ? " and form ID: {$formId}" : ""),
            ]);
        }

        // Get participant - normalize email to lowercase for case-insensitive lookup
        // This ensures emails like "AliAlaa@gmail.com" and "alialaa@gmail.com" match correctly
        $normalizedEmail = strtolower(trim($email));
        $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

        // If participant doesn't exist, create a new one with minimal required data
        if (!$participant) {
            // Extract name from form submissions if available, otherwise use email prefix
            $name = $this->data['name'] ?? $this->data['participant_name'] ?? explode('@', $email)[0];

            // Extract phone from form submissions if available, generate unique one if not
            $phone = $this->data['phone'] ?? $this->data['participant_phone'] ?? null;
            if (!$phone) {
                // Generate a unique phone number if not provided
                do {
                    $phone = '050' . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                } while (Participant::where('phone', $phone)->exists());
            }

            // Create participant with minimal required fields
            $participant = Participant::create([
                'name' => $name,
                'email' => $normalizedEmail, // Store normalized email
                'phone' => $phone,
                'gender' => $this->data['gender'] ?? 'male', // Default to male if not provided
                'date_of_birth' => $this->data['date_of_birth'] ?? now()->subYears(20)->format('Y-m-d'), // Default age 20
                'nationality_id' => $this->data['nationality_id'] ?? Nationality::query()->first()?->id,
                'country_id' => $this->data['country_id'] ?? Country::query()->first()?->id,
                'residence_city_id' => $this->data['residence_city_id'] ?? City::query()->first()?->id,
                'educational_background' => $this->data['educational_background'] ?? 'bachelor',
                'current_role' => $this->data['current_role'] ?? 'university_student',
                'years_of_experience' => $this->data['years_of_experience'] ?? 'no_experience',
                'password' => \Illuminate\Support\Str::random(16), // Generate random password
                'email_verified_at' => now(), // Auto-verify imported participants
                'is_active' => true,
            ]);
        }

        // Check if application already exists
        $existingApplication = CompetitionApplication::where('form_id', $form->id)
            ->where('participant_id', $participant->id)
            ->where('is_archived', false)
            ->first();

        if ($existingApplication) {
            // Throw ImportValidationException to mark this row as failed
            // This will be handled by Filament and stored in failed rows with the error message
            // The error message will appear in the failed rows sheet and can be downloaded
            throw ImportValidationException::withMessages([
                'email' => "Application already exists for this participant (email: {$email}) in this competition",
            ]);
        }

        // Validate track and sub_track if provided
        $track = null;
        $subTrack = null;

        if (isset($this->data['track']) && $this->data['track']) {
            $trackValue = $this->data['track'];
            $track = is_numeric($trackValue)
                ? Track::where('id', $trackValue)->where('competition_id', $competitionId)->first()
                : Track::where('slug', $trackValue)->where('competition_id', $competitionId)->first();

            if (!$track) {
                throw ImportValidationException::withMessages([
                    'track' => "The selected track is invalid or does not belong to competition ID: {$competitionId}.",
                ]);
            }
        }

        if (isset($this->data['sub_track']) && $this->data['sub_track']) {
            $subTrackValue = trim((string) $this->data['sub_track']);
            if (empty($subTrackValue)) {
                // Skip if empty after trimming
                $subTrackValue = null;
            }
            $subTrack = null;

            if ($subTrackValue && is_numeric($subTrackValue)) {
                // Cast to integer for proper ID matching
                $subTrackId = (int) $subTrackValue;

                // First, try to find by ID with competition check using whereHas
                $subTrack = SubTrack::where('id', $subTrackId)
                    ->whereHas('track', function ($query) use ($competitionId) {
                        $query->where('competition_id', $competitionId);
                    })
                    ->first();

                // If not found with whereHas, try finding the subtrack first, then verify manually
                // This handles cases where whereHas might fail due to relationship issues
                if (!$subTrack) {
                    $subTrack = SubTrack::with('track')->find($subTrackId);
                    if ($subTrack) {
                        // Verify it belongs to the competition
                        if (!$subTrack->track) {
                            $subTrack = null; // Subtrack has no track
                        } elseif ((int)$subTrack->track->competition_id !== (int)$competitionId) {
                            $subTrack = null; // Subtrack belongs to different competition
                        }
                    }
                }
            } elseif ($subTrackValue) {
                // Normalize the input value to slug format for comparison
                $normalizedValue = $this->normalizeToSlug($subTrackValue);

                // First, try exact slug match
                $subTrack = SubTrack::where('slug', $subTrackValue)
                    ->whereHas('track', function ($query) use ($competitionId) {
                        $query->where('competition_id', $competitionId);
                    })
                    ->first();

                // If not found, try normalized slug match
                if (!$subTrack) {
                    $subTrack = SubTrack::where('slug', $normalizedValue)
                        ->whereHas('track', function ($query) use ($competitionId) {
                            $query->where('competition_id', $competitionId);
                        })
                        ->first();
                }

                // If still not found, try matching by name (English or Arabic)
                if (!$subTrack) {
                    $subTrack = SubTrack::whereHas('track', function ($query) use ($competitionId) {
                            $query->where('competition_id', $competitionId);
                        })
                        ->get()
                        ->first(function ($st) use ($subTrackValue, $normalizedValue) {
                            // Check if the input matches the slug
                            if ($st->slug === $subTrackValue || $st->slug === $normalizedValue) {
                                return true;
                            }

                            // Check if the input matches the name (English or Arabic)
                            $name = $st->name;
                            if (is_array($name)) {
                                $nameEn = $name['en'] ?? '';
                                $nameAr = $name['ar'] ?? '';

                                // Check exact match
                                if ($nameEn === $subTrackValue || $nameAr === $subTrackValue) {
                                    return true;
                                }

                                // Check normalized match
                                $normalizedNameEn = $this->normalizeToSlug($nameEn);
                                $normalizedNameAr = $this->normalizeToSlug($nameAr);
                                if ($normalizedNameEn === $normalizedValue || $normalizedNameAr === $normalizedValue) {
                                    return true;
                                }
                            }

                            return false;
                        });
                }
            }

            if (!$subTrack) {
                throw ImportValidationException::withMessages([
                    'sub_track' => "The selected sub-track is invalid or does not belong to competition ID: {$competitionId}.",
                ]);
            }
        }

        // Validate that the selected Sub-track belongs to the chosen Track (when both are provided)
        if ($track && $subTrack && (int) $subTrack->track_id !== (int) $track->id) {
            throw ImportValidationException::withMessages([
                'sub_track' => 'The selected Sub-track does not belong to the chosen Track.',
            ]);
        }

        // Validate all form fields before building form_submissions
        // This will throw ImportValidationException if there are any validation errors
        $this->validateFormFields($form);

        // Build form_submissions from dynamic fields
        $formSubmissions = $this->buildFormSubmissions($form);

        // If there are skipped fields, add warnings (non-blocking)
        if (!empty($this->skippedFields)) {
            $warnings = [];
            $warningsByReason = [];
            foreach ($this->skippedFields as $skipped) {
                $warningMsg = "Field '{$skipped['field']}' ({$skipped['slug']}) was skipped: {$skipped['reason']}";
                $warnings[] = $warningMsg;

                // Group by reason for summary
                $reason = $skipped['reason'] ?? 'Unknown reason';
                if (!isset($warningsByReason[$reason])) {
                    $warningsByReason[$reason] = [];
                }
                $warningsByReason[$reason][] = $skipped['field'] . ' (' . $skipped['slug'] . ')';
            }
        }

        // Add track and sub_track to form_submissions if they exist in data but not in form fields
        if (isset($this->data['track']) && $this->data['track']) {
            $trackValue = $this->data['track'];
            $track = is_numeric($trackValue)
                ? Track::where('id', $trackValue)->where('competition_id', $competitionId)->first()
                : Track::where('slug', $trackValue)->where('competition_id', $competitionId)->first();

            if ($track) {
                $formSubmissions['track'] = $track->id;
            }
        }

        if (isset($this->data['sub_track']) && $this->data['sub_track']) {
            $subTrackValue = trim((string) $this->data['sub_track']);
            if (empty($subTrackValue)) {
                // Skip if empty after trimming
                $subTrackValue = null;
            }
            $subTrack = null;

            if ($subTrackValue && is_numeric($subTrackValue)) {
                // Cast to integer for proper ID matching
                $subTrackId = (int) $subTrackValue;

                // First, try to find by ID with competition check using whereHas
                $subTrack = SubTrack::where('id', $subTrackId)
                    ->whereHas('track', function ($query) use ($competitionId) {
                        $query->where('competition_id', $competitionId);
                    })
                    ->first();

                // If not found with whereHas, try finding the subtrack first, then verify manually
                // This handles cases where whereHas might fail due to relationship issues
                if (!$subTrack) {
                    $subTrack = SubTrack::with('track')->find($subTrackId);
                    if ($subTrack) {
                        // Verify it belongs to the competition
                        if (!$subTrack->track) {
                            $subTrack = null; // Subtrack has no track
                        } elseif ((int)$subTrack->track->competition_id !== (int)$competitionId) {
                            $subTrack = null; // Subtrack belongs to different competition
                        }
                    }
                }
            } elseif ($subTrackValue) {
                // Normalize the input value to slug format for comparison
                $normalizedValue = $this->normalizeToSlug($subTrackValue);

                // First, try exact slug match
                $subTrack = SubTrack::where('slug', $subTrackValue)
                    ->whereHas('track', function ($query) use ($competitionId) {
                        $query->where('competition_id', $competitionId);
                    })
                    ->first();

                // If not found, try normalized slug match
                if (!$subTrack) {
                    $subTrack = SubTrack::where('slug', $normalizedValue)
                        ->whereHas('track', function ($query) use ($competitionId) {
                            $query->where('competition_id', $competitionId);
                        })
                        ->first();
                }

                // If still not found, try matching by name (English or Arabic)
                if (!$subTrack) {
                    $subTrack = SubTrack::whereHas('track', function ($query) use ($competitionId) {
                            $query->where('competition_id', $competitionId);
                        })
                        ->get()
                        ->first(function ($st) use ($subTrackValue, $normalizedValue) {
                            // Check if the input matches the slug
                            if ($st->slug === $subTrackValue || $st->slug === $normalizedValue) {
                                return true;
                            }

                            // Check if the input matches the name (English or Arabic)
                            $name = $st->name;
                            if (is_array($name)) {
                                $nameEn = $name['en'] ?? '';
                                $nameAr = $name['ar'] ?? '';

                                // Check exact match
                                if ($nameEn === $subTrackValue || $nameAr === $subTrackValue) {
                                    return true;
                                }

                                // Check normalized match
                                $normalizedNameEn = $this->normalizeToSlug($nameEn);
                                $normalizedNameAr = $this->normalizeToSlug($nameAr);
                                if ($normalizedNameEn === $normalizedValue || $normalizedNameAr === $normalizedValue) {
                                    return true;
                                }
                            }

                            return false;
                        });
                }
            }

            if ($subTrack) {
                $formSubmissions['sub_track'] = $subTrack->id;
            }
        }

        // Extract team-related fields if present
        // For imported applications, always set as individual
        $registeredAs = 'individual';
        $hasTeam = false;
        $teamName = null;
        $teamLogo = null;
        $teamSerial = null;

        // Remove team fields from form_submissions as they're stored separately
        $formSubmissions = Arr::except($formSubmissions, ['register_as', 'has_team', 'team_name', 'team_logo', 'team_serial']);

        $application = new CompetitionApplication([
            'competition_id' => $competitionId,
            'form_id' => $form->id,
            'participant_id' => $participant->id,
            'form_submissions' => $formSubmissions,
            'registered_as' => $registeredAs,
            'has_team' => $hasTeam,
            'team_name' => $teamName,
            'team_logo' => $teamLogo,
            'team_serial' => $teamSerial,
            'status' => 'approved',
            'type' => 'submission',
        ]);

        return $application;
    }

    protected function validateFormFields(Form $form): void
    {
        $formFields = $form->fields()->orderBy('sort')->get();
        $errors = [];

        foreach ($formFields as $field) {
            $slug = $field->slug;
            $fieldType = $field->type;
            $value = $this->data[$slug] ?? null;

            // Normalize empty strings to null for consistent comparison
            if ($value === '') {
                $value = null;
            }

            // Check conditional logic - skip ALL validation (including "required")
            // if the field should be hidden based on current data.
            $shouldShow = true;
            $conditionalLogicError = null;
            if ($field->conditional_logic && $field->conditional_logic_rules) {
                $shouldShow = $this->evaluateConditionalLogic($field, $this->data, $form);
                if (!$shouldShow) {
                    // Field is hidden by conditional logic, skip validation
                    // But if field has a value, it means the data doesn't match the condition
                    // This is an error - the value was provided but condition not met

                    // Check if value exists (handle various empty cases)
                    $hasValue = false;
                    if ($value !== null && $value !== '') {
                        // Also check for empty arrays and whitespace-only strings
                        if (is_array($value)) {
                            $hasValue = !empty($value);
                        } elseif (is_string($value)) {
                            $hasValue = trim($value) !== '';
                        } else {
                            $hasValue = true;
                        }
                    }

                    if ($hasValue) {
                        $fieldLabel = is_array($field->label)
                            ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                            : ($field->label ?? $slug);

                        // Build detailed error message: only the defined condition values (no extra/internal conditions)
                        $rules = $field->conditional_logic_rules;
                        $conditions = [];
                        foreach ($rules as $rule) {
                            $fieldId = $rule['field_id'] ?? null;
                            if (!$fieldId) {
                                continue;
                            }

                            // Use same expansion as evaluation so message matches actual allowed values (en, ar, split "en,ar")
                            $expectedValues = [];
                            foreach ($rule['values'] ?? [] as $val) {
                                foreach ($this->expandConditionalExpectedValues($val) as $v) {
                                    if ($v !== null && $v !== '' && !in_array($v, $expectedValues, true)) {
                                        $expectedValues[] = $v;
                                    }
                                }
                            }

                            $dependentField = $form->fields()->where('slug', $fieldId)->first();
                            $dependentFieldLabel = $dependentField
                                ? (is_array($dependentField->label)
                                    ? ($dependentField->label['en'] ?? $dependentField->label['ar'] ?? $fieldId)
                                    : ($dependentField->label ?? $fieldId))
                                : $fieldId;

                            $dependentValue = $this->data[$fieldId] ?? null;
                            $displayValue = $dependentValue;
                            if (is_array($displayValue)) {
                                $displayValue = implode(', ', $displayValue);
                            }
                            $displayValue = $displayValue ?? 'empty';

                            if (!empty($expectedValues)) {
                                $conditions[] = "'{$dependentFieldLabel}' must equal one of: " . implode(', ', $expectedValues) . " (current value: {$displayValue})";
                            } else {
                                $conditions[] = "'{$dependentFieldLabel}' condition not met (current value: {$displayValue})";
                            }
                        }

                        $fieldLabelEn = is_array($field->label) ? ($field->label['en'] ?? $slug) : ($field->label ?? $slug);
                        $fieldLabelAr = is_array($field->label) ? ($field->label['ar'] ?? $slug) : ($field->label ?? $slug);

                        // Build error message with fallback if conditions array is empty
                        if (!empty($conditions)) {
                            $errorMsg = "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' has a value but conditional logic requirements are not met. Required conditions: " . implode(' OR ', $conditions) . ". Please fix the data or remove the value.";
                        } else {
                            $errorMsg = "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' has a value but conditional logic requirements are not met. The field should be hidden based on the current data. Please fix the data or remove the value.";
                        }

                        // Always add the error - ensure it's not empty
                        if (!empty($errorMsg)) {
                            $errors[$slug] = $errorMsg;
                        }
                    }
                    // Field is hidden and has an invalid value – we've logged an error already.
                    // Do not run any further validation (including "required") on this field.
                    continue;
                }
            }

            // If conditional logic says the field is hidden AND there is no value,
            // completely skip validation, even if the field is marked as required.
            if (!$shouldShow) {
                continue;
            }

            // If field should be shown (either no conditional logic or condition met),
            // validate required fields.
            //
            // IMPORTANT (import-specific behavior):
            // For fields that have conditional logic configured, we NEVER enforce "required"
            // during import, even if the condition is evaluated as met.
            // This prevents conditional fields (like multi-select dropdowns) from blocking
            // imports with "is required" errors when their visibility/conditions are complex.
            if (
                $shouldShow
                && $field->required
                && !($field->conditional_logic && $field->conditional_logic_rules)
                && ($value === null || $value === '')
            ) {
                $fieldLabelEn = is_array($field->label)
                    ? ($field->label['en'] ?? $slug)
                    : ($field->label ?? $slug);
                $fieldLabelAr = is_array($field->label)
                    ? ($field->label['ar'] ?? $slug)
                    : ($field->label ?? $slug);
                $errors[$slug] = "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' is required. / الحقل '{$fieldLabelAr}' مطلوب.";
                continue;
            }

            // Skip validation if no value and field is not required
            if ($value === null || $value === '') {
                continue;
            }

            // Validate field type-specific constraints BEFORE other validations
            $typeValidationError = $this->validateFieldType($field, $value);
            if ($typeValidationError) {
                $errors[$slug] = $typeValidationError;
                continue;
            }

            // Validate dropdown, radio, and rating fields
            if (in_array($fieldType, ['dropdown', 'radio', 'rating'])) {
                if ($field->options && is_array($field->options)) {
                    $validOptions = [];
                    $validLabels = [];

                    // Check if options are stored as strings (en/ar format)
                    if (isset($field->options['en']) && isset($field->options['ar']) &&
                        is_string($field->options['en']) && is_string($field->options['ar'])) {
                        // Parse string format options
                        $enOptions = FormField::parseOptionsString($field->options['en']);
                        $arOptions = FormField::parseOptionsString($field->options['ar']);
                        $validOptions = array_merge($validOptions, $enOptions);
                        $validLabels = array_merge($validLabels, $arOptions);
                    } else {
                        // Array format
                        foreach ($field->options as $option) {
                            if (is_array($option)) {
                                if (isset($option['value'])) {
                                    $validOptions[] = $option['value'];
                                }
                                if (isset($option['label'])) {
                                    if (is_array($option['label'])) {
                                        $validLabels = array_merge($validLabels, array_values($option['label']));
                                    } else {
                                        $validLabels[] = $option['label'];
                                    }
                                }
                                // Check for en/ar format
                                if (isset($option['en'])) {
                                    $validLabels[] = $option['en'];
                                }
                                if (isset($option['ar'])) {
                                    $validLabels[] = $option['ar'];
                                }
                            } elseif (is_string($option)) {
                                $validOptions[] = $option;
                            }
                        }
                    }

                    // Check if value matches any valid option or label
                    $isValid = in_array($value, $validOptions) || in_array($value, $validLabels);

                    if (!$isValid) {
                        $fieldLabelEn = is_array($field->label) ? ($field->label['en'] ?? $slug) : ($field->label ?? $slug);
                        $fieldLabelAr = is_array($field->label) ? ($field->label['ar'] ?? $slug) : ($field->label ?? $slug);
                        $allValid = array_unique(array_merge($validOptions, $validLabels));
                        $errors[$slug] = "Invalid value '{$value}' for field '{$fieldLabelEn}' / '{$fieldLabelAr}'. Valid options: " . implode(', ', array_slice($allValid, 0, 10)) . (count($allValid) > 10 ? '...' : '');
                    }
                }
            }

            // Validate checkbox and multi_select fields
            if (in_array($fieldType, ['checkbox', 'multi_select'])) {
                if ($field->options && is_array($field->options)) {
                    $validOptions = [];
                    $validLabels = [];

                    // Check if options are stored as strings (en/ar format)
                    if (isset($field->options['en']) && isset($field->options['ar']) &&
                        is_string($field->options['en']) && is_string($field->options['ar'])) {
                        // Parse string format options
                        $enOptions = FormField::parseOptionsString($field->options['en']);
                        $arOptions = FormField::parseOptionsString($field->options['ar']);
                        $validOptions = array_merge($validOptions, $enOptions);
                        $validLabels = array_merge($validLabels, $arOptions);
                    } else {
                        // Array format
                        foreach ($field->options as $option) {
                            if (is_array($option)) {
                                if (isset($option['value'])) {
                                    $validOptions[] = $option['value'];
                                }
                                if (isset($option['label'])) {
                                    if (is_array($option['label'])) {
                                        $validLabels = array_merge($validLabels, array_values($option['label']));
                                    } else {
                                        $validLabels[] = $option['label'];
                                    }
                                }
                                // Check for en/ar format
                                if (isset($option['en'])) {
                                    $validLabels[] = $option['en'];
                                }
                                if (isset($option['ar'])) {
                                    $validLabels[] = $option['ar'];
                                }
                            } elseif (is_string($option)) {
                                $validOptions[] = $option;
                            }
                        }
                    }

                    // Handle comma-separated values
                    $values = is_array($value) ? $value : (is_string($value) ? array_map('trim', explode(',', $value)) : [$value]);

                    foreach ($values as $val) {
                        $isValid = in_array($val, $validOptions) || in_array($val, $validLabels);
                        if (!$isValid) {
                            $fieldLabelEn = is_array($field->label) ? ($field->label['en'] ?? $slug) : ($field->label ?? $slug);
                            $fieldLabelAr = is_array($field->label) ? ($field->label['ar'] ?? $slug) : ($field->label ?? $slug);
                            $allValid = array_unique(array_merge($validOptions, $validLabels));
                            $errors[$slug] = "Invalid value '{$val}' for field '{$fieldLabelEn}' / '{$fieldLabelAr}'. Valid options: " . implode(', ', array_slice($allValid, 0, 10)) . (count($allValid) > 10 ? '...' : '');
                            break;
                        }
                    }
                }
            }

            // Validate custom validation rules (field should be shown at this point, only validate if has value)
            if ($field->validation_rules && is_array($field->validation_rules) && $value !== null && $value !== '') {
                foreach ($field->validation_rules as $rule) {
                    // Support both 'type' and 'rule' keys for compatibility
                    $ruleType = $rule['type'] ?? $rule['rule'] ?? null;
                    $ruleValue = $rule['value'] ?? null;

                    // Date/time rules use value_date, value_time, start_date, end_date, etc. — do not skip when value is null
                    $dateTimeRuleTypes = ['after', 'before', 'after_or_equal', 'before_or_equal', 'between', 'after_time', 'before_time', 'between_time'];
                    if ($ruleType === null) {
                        continue;
                    }
                    if ($ruleValue === null && !in_array($ruleType, $dateTimeRuleTypes, true)) {
                        continue;
                    }

                    $fieldLabel = is_array($field->label)
                        ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                        : ($field->label ?? $slug);

                    // Determine if field is text-based (text, textarea) for max/min length validation
                    $isTextField = in_array($fieldType, ['text', 'textarea']);

                    switch ($ruleType) {
                        case 'min_length':
                        case 'min':
                            // For text fields, 'min' means minimum length
                            if ($isTextField && $ruleType === 'min') {
                                // Handle both string and array values
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_string($val) && strlen($val) < (int)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_string($value) && strlen($value) < (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                                }
                            } elseif ($ruleType === 'min_length') {
                                // Handle both string and array values
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_string($val) && strlen($val) < (int)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_string($value) && strlen($value) < (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                                }
                            } else {
                                // For numeric fields, 'min' means minimum value
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_numeric($val) && (float)$val < (float)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue}.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_numeric($value) && (float)$value < (float)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue}.";
                                }
                            }
                            break;

                        case 'max_length':
                        case 'max':
                            // Skip max validation for file fields - handled in validateFieldType
                            if ($fieldType === 'file') {
                                break;
                            }
                            // For text fields, 'max' means maximum length
                            if ($isTextField && $ruleType === 'max') {
                                // Handle both string and array values
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_string($val) && strlen($val) > (int)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_string($value) && strlen($value) > (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                                }
                            } elseif ($ruleType === 'max_length') {
                                // Handle both string and array values
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_string($val) && strlen($val) > (int)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_string($value) && strlen($value) > (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                                }
                            } else {
                                // For numeric fields, 'max' means maximum value
                                if (is_array($value)) {
                                    foreach ($value as $val) {
                                        if (is_numeric($val) && (float)$val > (float)$ruleValue) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue}.";
                                            break 2;
                                        }
                                    }
                                } elseif (is_numeric($value) && (float)$value > (float)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue}.";
                                }
                            }
                            break;

                        case 'pattern':
                        case 'regex':
                            $pattern = $ruleType === 'regex' ? ($ruleValue['pattern'] ?? $ruleValue) : $ruleValue;
                            // Ensure pattern has delimiters for preg_match (e.g. form may store "^[0-9]+$" without /)
                            if (is_string($pattern) && strlen($pattern) > 0 && !in_array($pattern[0], ['/', '#', '~', '%', '!'], true)) {
                                $pattern = '/' . str_replace('/', '\\/', $pattern) . '/';
                            }
                            // Handle both string and array values
                            if (is_array($value)) {
                                foreach ($value as $val) {
                                    if (is_string($val) && !preg_match($pattern, $val)) {
                                        $errors[$slug] = "The field '{$fieldLabel}' format is invalid (phone/regex validation).";
                                        break 2;
                                    }
                                }
                            } elseif (is_string($value) && !preg_match($pattern, $value)) {
                                $errors[$slug] = "The field '{$fieldLabel}' format is invalid (phone/regex validation).";
                            }
                            break;

                        // Date validation rules (for date fields)
                        case 'after':
                        case 'before':
                        case 'after_or_equal':
                        case 'before_or_equal':
                            if ($fieldType === 'date' && is_string($value)) {
                                $boundDateStr = $rule['value_date'] ?? null;
                                if ($boundDateStr) {
                                    $boundDate = \Carbon\Carbon::parse($boundDateStr)->startOfDay();
                                    $parsedValue = $this->parseDateForValidation($value);
                                    if ($parsedValue) {
                                        $violation = false;
                                        if ($ruleType === 'after' && $parsedValue->lte($boundDate)) {
                                            $violation = true;
                                        } elseif ($ruleType === 'before' && $parsedValue->gte($boundDate)) {
                                            $violation = true;
                                        } elseif ($ruleType === 'after_or_equal' && $parsedValue->lt($boundDate)) {
                                            $violation = true;
                                        } elseif ($ruleType === 'before_or_equal' && $parsedValue->gt($boundDate)) {
                                            $violation = true;
                                        }
                                        if ($violation) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must be " . ($ruleType === 'after' ? 'after' : ($ruleType === 'before' ? 'before' : ($ruleType === 'after_or_equal' ? 'on or after' : 'on or before'))) . " {$boundDateStr}.";
                                            break 2;
                                        }
                                    }
                                }
                            }
                            break;

                        case 'between':
                            if ($fieldType === 'date' && is_string($value)) {
                                $startDateStr = $rule['start_date'] ?? null;
                                $endDateStr = $rule['end_date'] ?? null;
                                if ($startDateStr && $endDateStr) {
                                    $parsedValue = $this->parseDateForValidation($value);
                                    $startDate = \Carbon\Carbon::parse($startDateStr)->startOfDay();
                                    $endDate = \Carbon\Carbon::parse($endDateStr)->endOfDay();
                                    if ($parsedValue && ($parsedValue->lt($startDate) || $parsedValue->gt($endDate))) {
                                        $errors[$slug] = "The field '{$fieldLabel}' must be a date between {$startDateStr} and {$endDateStr}.";
                                        break 2;
                                    }
                                }
                            }
                            break;

                        // Time validation rules (for time fields)
                        case 'after_time':
                        case 'before_time':
                            if ($fieldType === 'time' && $value !== null && $value !== '' && is_string($value)) {
                                $boundTimeStr = $rule['value_time'] ?? null;
                                if ($boundTimeStr) {
                                    $boundTime = \Carbon\Carbon::parse($boundTimeStr);
                                    $parsedTime = $this->parseTimeForValidation($value);
                                    if ($parsedTime) {
                                        $violation = false;
                                        if ($ruleType === 'after_time' && $parsedTime->lte($boundTime)) {
                                            $violation = true;
                                        } elseif ($ruleType === 'before_time' && $parsedTime->gte($boundTime)) {
                                            $violation = true;
                                        }
                                        if ($violation) {
                                            $errors[$slug] = "The field '{$fieldLabel}' must be " . ($ruleType === 'after_time' ? 'after' : 'before') . " {$boundTimeStr}.";
                                            break 2;
                                        }
                                    }
                                }
                            }
                            break;

                        case 'between_time':
                            if ($fieldType === 'time' && $value !== null && $value !== '' && is_string($value)) {
                                $startTimeStr = $rule['start_time'] ?? null;
                                $endTimeStr = $rule['end_time'] ?? null;
                                if ($startTimeStr && $endTimeStr) {
                                    $parsedTime = $this->parseTimeForValidation($value);
                                    $startTime = \Carbon\Carbon::parse($startTimeStr);
                                    $endTime = \Carbon\Carbon::parse($endTimeStr);
                                    if ($parsedTime && ($parsedTime->lt($startTime) || $parsedTime->gt($endTime))) {
                                        $errors[$slug] = "The field '{$fieldLabel}' must be a time between {$startTimeStr} and {$endTimeStr}.";
                                        break 2;
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }

        // Throw exception if there are validation errors
        if (!empty($errors)) {
            throw ImportValidationException::withMessages($errors);
        }
    }

    /**
     * Validate field type-specific constraints
     */
    protected function validateFieldType(FormField $field, $value): ?string
    {
        $fieldLabelEn = is_array($field->label) ? ($field->label['en'] ?? $field->slug) : ($field->label ?? $field->slug);
        $fieldLabelAr = is_array($field->label) ? ($field->label['ar'] ?? $field->slug) : ($field->label ?? $field->slug);

        // Validate file fields - format + validation rules (extensions, max size, file existence)
        if ($field->type === 'file') {
            if (is_numeric($value)) {
                return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a file path or URL, not a number. / يجب أن يكون الحقل '{$fieldLabelAr}' مسار ملف أو رابط، وليس رقماً.";
            }
            if (!is_string($value)) {
                return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid file path or URL. / يجب أن يكون الحقل '{$fieldLabelAr}' مسار ملف أو رابط صحيح.";
            }

            // Check basic shape (path/URL)
            $isFilePath = (
                str_contains($value, '/') ||
                str_contains($value, '\\') ||
                str_contains($value, '.') ||
                str_starts_with($value, 'http://') ||
                str_starts_with($value, 'https://') ||
                str_starts_with($value, 'storage/') ||
                str_starts_with($value, 'uploads/')
            );
            if (!$isFilePath) {
                return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid file path or URL. / يجب أن يكون الحقل '{$fieldLabelAr}' مسار ملف أو رابط صحيح.";
            }

            // Extract file path (remove query string for URLs)
            $filePath = explode('?', $value, 2)[0];
            $isUrl = str_starts_with($value, 'http://') || str_starts_with($value, 'https://');

            // Check file size validation (for both local files and URLs)
            $maxLimit = $this->resolveMaxFileSizeFromField($field);

            if ($isUrl) {
                // Check file size for URLs using HTTP HEAD request
                if ($maxLimit !== null) {
                    $urlFileSize = $this->getUrlFileSize($value);
                    if ($urlFileSize !== null && $urlFileSize > $maxLimit['bytes']) {
                        $actualSizeMB = round($urlFileSize / 1024 / 1024, 2);
                        return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' file size ({$actualSizeMB}MB) exceeds the maximum allowed size ({$maxLimit['mb']}MB). / حجم ملف الحقل '{$fieldLabelAr}' ({$actualSizeMB} ميجابايت) يتجاوز الحد الأقصى المسموح به ({$maxLimit['mb']} ميجابايت).";
                    }
                }
            } else {
                // Check if file exists (for local paths only, not URLs)
                // Try to resolve relative paths
                $resolvedPath = null;

                // Check if it's a storage path
                if (str_starts_with($filePath, 'storage/')) {
                    $resolvedPath = storage_path('app/public/' . substr($filePath, 8));
                } elseif (str_starts_with($filePath, 'uploads/')) {
                    $resolvedPath = public_path($filePath);
                } elseif (str_starts_with($filePath, '/')) {
                    // Absolute path
                    $resolvedPath = $filePath;
                } else {
                    // Relative path - try public and storage
                    $resolvedPath = public_path($filePath);
                    if (!file_exists($resolvedPath)) {
                        $resolvedPath = storage_path('app/public/' . $filePath);
                    }
                }

                if ($resolvedPath && !file_exists($resolvedPath)) {
                    return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' references a file that does not exist: {$filePath} / الحقل '{$fieldLabelAr}' يشير إلى ملف غير موجود: {$filePath}";
                }

                // Check file size if file exists (Max File Size from validation_rules: max_file_size in MB or rule "max" value in KB)
                if ($resolvedPath && file_exists($resolvedPath)) {
                    $fileSize = filesize($resolvedPath);
                    if ($maxLimit !== null && $fileSize > $maxLimit['bytes']) {
                        $actualSizeMB = round($fileSize / 1024 / 1024, 2);
                        return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' file size ({$actualSizeMB}MB) exceeds the maximum allowed size ({$maxLimit['mb']}MB). / حجم ملف الحقل '{$fieldLabelAr}' ({$actualSizeMB} ميجابايت) يتجاوز الحد الأقصى المسموح به ({$maxLimit['mb']} ميجابايت).";
                    }
                }
            }

            // Validate allowed file extensions/mimes from validation_rules (and Allowed File Types: allowed_mimes / allowed_mimes_string)
            $allowedExtensions = $this->resolveAllowedFileExtensionsFromField($field);

            if (!empty($allowedExtensions)) {
                // Extract extension from the value (after last dot, ignore query string)
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
                    $list = implode(', ', array_unique($allowedExtensions));
                    return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a file of type: {$list}. / يجب أن يكون الحقل '{$fieldLabelAr}' ملفاً من الأنواع التالية: {$list}.";
                }
            }
        }

        // Validate text/textarea fields - should reject file paths
        if (in_array($field->type, ['text', 'textarea'])) {
            if (is_string($value)) {
                // Check if it looks like a file path
                $looksLikeFilePath = (
                    str_contains($value, '/storage/') ||
                    str_contains($value, '/uploads/') ||
                    str_contains($value, '\\storage\\') ||
                    str_contains($value, '\\uploads\\') ||
                    (str_contains($value, '.') && preg_match('/\.(jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|zip|rar|mp4|avi|mov)$/i', $value))
                );
                if ($looksLikeFilePath) {
                    return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' is a text field but contains a file path. Please use a file field instead. / الحقل '{$fieldLabelAr}' هو حقل نصي لكنه يحتوي على مسار ملف. يرجى استخدام حقل ملف بدلاً من ذلك.";
                }
            }
        }

        // Validate email fields
        if ($field->type === 'email' && is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid email address. / يجب أن يكون الحقل '{$fieldLabelAr}' عنوان بريد إلكتروني صحيح.";
        }

        // Validate URL fields
        if ($field->type === 'url' && is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid URL. / يجب أن يكون الحقل '{$fieldLabelAr}' رابطاً صحيحاً.";
        }

        // Validate number fields
        if ($field->type === 'number' && !is_numeric($value)) {
            return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a number. / يجب أن يكون الحقل '{$fieldLabelAr}' رقماً.";
        }

        // Validate phone fields - optional + prefix, 8-15 digits (spaces/dashes allowed between digits)
        if ($field->type === 'phone' && $value !== null && $value !== '') {
            $phoneStr = is_string($value) ? trim($value) : (string) $value;
            if ($phoneStr !== '') {
                $normalized = preg_replace('/[\s\-\.\(\)]/', '', $phoneStr);
                if (!preg_match('/^\+?[0-9]{8,15}$/', $normalized)) {
                    return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid phone number (e.g. +966512345678 or 0501234567, 8-15 digits). / يجب أن يكون الحقل '{$fieldLabelAr}' رقماً جوّالاً صحيحاً (مثال: 0501234567، 8-15 رقماً).";
                }
            }
        }

        // Validate date fields - support multiple formats
        if ($field->type === 'date' && is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null; // Empty values are handled by required validation
            }

            // Try different date formats
            $dateFormats = [
                'd/m/Y',      // 01/01/2024 (DD/MM/YYYY)
                'm/d/Y',      // 01/01/2024 (MM/DD/YYYY)
                'Y-m-d',      // 2024-01-01 (ISO format)
                'd-m-Y',      // 01-01-2024 (DD-MM-YYYY)
                'Y/m/d',      // 2024/01/01
                'd.m.Y',      // 01.01.2024
                'Y.m.d',      // 2024.01.01
            ];

            $parsed = false;
            foreach ($dateFormats as $format) {
                try {
                    $date = \Carbon\Carbon::createFromFormat($format, $value);
                    if ($date) {
                        $parsed = true;
                        break;
                    }
                } catch (\Exception $e) {
                    // Try next format
                    continue;
                }
            }

            // If all specific formats failed, try Carbon's flexible parser
            if (!$parsed) {
                try {
                    $date = \Carbon\Carbon::parse($value);
                    if ($date) {
                        $parsed = true;
                    }
                } catch (\Exception $e) {
                    // Parsing failed
                }
            }

            if (!$parsed) {
                return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid date. Supported formats: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD, etc. / يجب أن يكون الحقل '{$fieldLabelAr}' تاريخاً صحيحاً. الصيغ المدعومة: DD/MM/YYYY، MM/DD/YYYY، YYYY-MM-DD، إلخ.";
            }
        }

        // Validate time picker fields - accept common time formats
        if ($field->type === 'time' && $value !== null && $value !== '') {
            $timeStr = is_string($value) ? trim($value) : (string) $value;
            if ($timeStr === '') {
                return null;
            }
            $timeFormats = [
                'H:i',       // 14:30 (24h)
                'H:i:s',     // 14:30:00 (24h with seconds)
                'h:i A',     // 02:30 PM (12h)
                'h:i a',     // 02:30 pm
                'g:i A',     // 2:30 PM (no leading zero)
                'g:i a',
            ];
            $parsedTime = false;
            foreach ($timeFormats as $format) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($format, $timeStr);
                    if ($parsed) {
                        $parsedTime = true;
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            if (!$parsedTime) {
                try {
                    $parsed = \Carbon\Carbon::parse($timeStr);
                    if ($parsed) {
                        $parsedTime = true;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
            if (!$parsedTime) {
                return "Field '{$fieldLabelEn}' / '{$fieldLabelAr}' must be a valid time. Supported formats: HH:MM (24h), HH:MM:SS, or 2:30 PM. / يجب أن يكون الحقل '{$fieldLabelAr}' وقتاً صحيحاً. الصيغ المدعومة: HH:MM، HH:MM:SS، أو 2:30 م.";
            }
        }

        return null; // No validation error
    }

    /**
     * Resolve allowed file extensions for a file field from validation_rules (rule value, allowed_mimes_string, allowed_mimes).
     * Matches FormResource "Allowed File Types" storage: allowed_mimes (array) and allowed_mimes_string (comma-separated).
     */
    protected function resolveAllowedFileExtensionsFromField(FormField $field): array
    {
        $allowedExtensions = [];
        if (!is_array($field->validation_rules)) {
            return $allowedExtensions;
        }

        $expandAllMimes = [
            'all_images'   => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'ico'],
            'all_documents'=> ['pdf', 'doc', 'docx', 'rtf', 'txt', 'odt'],
            'all_archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'all_media'    => ['mp3', 'wav', 'flac', 'aac', 'ogg', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        ];

        foreach ($field->validation_rules as $rule) {
            $ruleType = $rule['type'] ?? $rule['rule'] ?? null;
            if (!in_array($ruleType, ['mimes', 'extensions', 'mimetypes'], true)) {
                continue;
            }

            $ruleValue = $rule['value'] ?? null;
            $allowedMimesString = $rule['allowed_mimes_string'] ?? null;
            $allowedMimes = $rule['allowed_mimes'] ?? null;

            $parts = [];
            if (is_string($ruleValue) && $ruleValue !== '') {
                $parts = preg_split('/[,،]/u', $ruleValue);
            } elseif (is_array($ruleValue) && !empty($ruleValue)) {
                $parts = $ruleValue;
            } elseif (is_string($allowedMimesString) && $allowedMimesString !== '') {
                $parts = array_map('trim', explode(',', $allowedMimesString));
            } elseif (is_array($allowedMimes) && !empty($allowedMimes)) {
                foreach ($allowedMimes as $selected) {
                    if (!is_string($selected) || $selected === '') {
                        continue;
                    }
                    if (isset($expandAllMimes[$selected])) {
                        foreach ($expandAllMimes[$selected] as $ext) {
                            $parts[] = $ext;
                        }
                    } elseif (!str_starts_with($selected, 'all_')) {
                        $parts[] = $selected;
                    }
                }
            }

            foreach ($parts as $ext) {
                $ext = is_string($ext) ? strtolower(trim($ext)) : '';
                if ($ext !== '') {
                    $allowedExtensions[] = ltrim($ext, '.');
                }
            }
        }

        return array_values(array_unique($allowedExtensions));
    }

    /**
     * Resolve max file size (in bytes and MB for messages) from field validation_rules.
     * Supports: max_file_size (MB) from form "Max File Size", and rule type "max" with value (KB).
     */
    protected function resolveMaxFileSizeFromField(FormField $field): ?array
    {
        if (!is_array($field->validation_rules)) {
            return null;
        }
        foreach ($field->validation_rules as $rule) {
            $maxMb = null;
            if (isset($rule['max_file_size']) && (is_numeric($rule['max_file_size']) || $rule['max_file_size'] === '0')) {
                $maxMb = (float) $rule['max_file_size'];
            }
            $ruleType = $rule['type'] ?? $rule['rule'] ?? null;
            $ruleValue = $rule['value'] ?? null;
            if ($maxMb === null && $ruleType === 'max' && $ruleValue !== null && (is_numeric($ruleValue) || $ruleValue === '0')) {
                $maxMb = (float) $ruleValue / 1024; // value is in KB
            }
            if ($maxMb !== null && $maxMb > 0) {
                return [
                    'bytes' => (int) round($maxMb * 1024 * 1024),
                    'mb' => round($maxMb, 2),
                ];
            }
        }
        return null;
    }

    /**
     * Get file size from a URL using HTTP HEAD request.
     * Returns file size in bytes, or null if unable to determine.
     */
    protected function getUrlFileSize(string $url): ?int
    {
        try {
            // Use cURL for better control and error handling
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true, // HEAD request only
                CURLOPT_HEADER => true, // Include headers in output
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, // Follow redirects
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 10, // 10 second timeout
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FileSizeChecker/1.0)',
            ]);

            $headers = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Check if request was successful
            if ($httpCode >= 200 && $httpCode < 300 && !$error) {
                // Extract Content-Length from headers
                if (preg_match('/Content-Length:\s*(\d+)/i', $headers, $matches)) {
                    return (int) $matches[1];
                }
            }

            // If HEAD request didn't work or Content-Length not available, return null
            // We don't want to download the entire file just to check size
            return null;
        } catch (\Exception $e) {
            // If any error occurs, return null (validation will proceed without size check for URLs)
            \Log::warning('Failed to get file size from URL during import', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse a string value as a date for validation (returns Carbon or null).
     */
    protected function parseDateForValidation(string $value): ?\Carbon\Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dateFormats = [
            'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d', 'd.m.Y', 'Y.m.d',
        ];
        foreach ($dateFormats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->startOfDay();
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        try {
            return \Carbon\Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a string value as a time for validation (returns Carbon or null).
     */
    protected function parseTimeForValidation(string $value): ?\Carbon\Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timeFormats = ['H:i', 'H:i:s', 'h:i A', 'h:i a', 'g:i A', 'g:i a'];
        foreach ($timeFormats as $format) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat($format, $value);
                if ($parsed) {
                    return $parsed;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normalize string for comparison (trim + Unicode NFC) so Arabic and bilingual values match consistently.
     */
    protected function normalizeConditionalValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (class_exists(\Normalizer::class) && \Normalizer::isNormalized($value, \Normalizer::FORM_C) === false) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $value = $normalized;
            }
        }
        return $value;
    }

    /**
     * Compare two values for conditional logic (Unicode-safe; supports Arabic and bilingual).
     */
    protected function conditionalLogicValuesEqual($dependent, $expected): bool
    {
        if ($dependent === $expected) {
            return true;
        }
        if ($dependent === null || $expected === null) {
            return false;
        }
        $d = is_string($dependent) ? $this->normalizeConditionalValue($dependent) : $dependent;
        $e = is_string($expected) ? $this->normalizeConditionalValue($expected) : $expected;
        if ($d === $e) {
            return true;
        }
        if (is_numeric($d) && is_numeric($e)) {
            return (float)$d === (float)$e;
        }
        if (is_string($d) && is_string($e)) {
            return mb_strtolower($d, 'UTF-8') === mb_strtolower($e, 'UTF-8');
        }
        return false;
    }

    /**
     * Expand a stored value (e.g. "Yes,نعم") into an array of allowed values for comparison.
     */
    protected function expandConditionalExpectedValues($val): array
    {
        $out = [];
        if (is_string($val) || is_numeric($val)) {
            $out[] = $val;
        } elseif (is_array($val)) {
            if (isset($val['value'])) {
                $out[] = $val['value'];
            }
            if (isset($val['en']) && $val['en'] !== null && $val['en'] !== '') {
                $out[] = is_string($val['en']) ? trim($val['en']) : $val['en'];
            }
            if (isset($val['ar']) && $val['ar'] !== null && $val['ar'] !== '') {
                $out[] = is_string($val['ar']) ? trim($val['ar']) : $val['ar'];
            }
            if (empty($out) && isset($val[0])) {
                $out[] = $val[0];
            }
        }
        $expanded = [];
        foreach ($out as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $v = is_string($v) ? trim($v) : $v;
            if ($v === '') {
                continue;
            }
            if (is_string($v) && (str_contains($v, ',') || str_contains($v, '،'))) {
                $parts = preg_split('/[,،]/u', $v);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && !in_array($p, $expanded, true)) {
                        $expanded[] = $p;
                    }
                }
            } else {
                if (!in_array($v, $expanded, true)) {
                    $expanded[] = $v;
                }
            }
        }
        return $expanded;
    }

    /**
     * Evaluate conditional logic rules to determine if a field should be shown
     */
    protected function evaluateConditionalLogic(FormField $field, array $data, ?Form $form = null): bool
    {
        if (!$field->conditional_logic || !$field->conditional_logic_rules) {
            return true; // No conditional logic, field is always shown
        }

        $rules = $field->conditional_logic_rules;
        if (!is_array($rules) || empty($rules)) {
            return true;
        }

        // Get the form to access other fields
        if (!$form) {
            $form = $field->form;
        }
        if (!$form) {
            // Can't evaluate without form context - default to showing field to avoid hiding valid fields
            \Log::warning("Cannot evaluate conditional logic for field {$field->slug}: form not available");
            return true;
        }

        // Evaluate each rule - any rule passing is enough (OR logic)
        foreach ($rules as $rule) {
            $fieldId = $rule['field_id'] ?? null;
            $operator = $rule['operator'] ?? 'equals';
            $values = $rule['values'] ?? [];

            if (!$fieldId) {
                continue;
            }

            // Get the dependent field to check its type
            $dependentField = $form->fields()->where('slug', $fieldId)->first();
            $dependentFieldType = $dependentField ? $dependentField->type : null;

            // Get the value of the dependent field - normalize empty strings to null
            // Also handle case where fieldId might be in different formats
            $dependentValue = $data[$fieldId] ?? null;

            // If not found, try to find by slug (in case fieldId is not exactly matching)
            if ($dependentValue === null && !isset($data[$fieldId])) {
                // Try to find the field by checking all keys (case-insensitive)
                foreach ($data as $key => $val) {
                    if (strtolower($key) === strtolower($fieldId)) {
                        $dependentValue = $val;
                        break;
                    }
                }
            }

            if ($dependentValue === '') {
                $dependentValue = null;
            }
            // Trim string values so " نعم " matches condition "نعم"
            if (is_string($dependentValue)) {
                $dependentValue = trim($dependentValue);
                if ($dependentValue === '') {
                    $dependentValue = null;
                }
            }

            // For checkbox/multi_select fields, always treat as array for comparison
            // Even if it's a single string value, it should be treated as an array
            if (in_array($dependentFieldType, ['checkbox', 'multi_select'])) {
                if (is_string($dependentValue) && !empty($dependentValue)) {
                    // Convert string to array (handle comma-separated or single value)
                    if (str_contains($dependentValue, ',')) {
                        $dependentValue = array_map('trim', explode(',', $dependentValue));
                    } else {
                        $dependentValue = [$dependentValue];
                    }
                } elseif (!is_array($dependentValue) && $dependentValue !== null) {
                    // Convert single non-array value to array
                    $dependentValue = [$dependentValue];
                }
            }

            // Normalize expected values (bilingual: en + ar; split "en,ar" so Arabic import values match)
            $expectedValues = [];
            foreach ($values as $val) {
                foreach ($this->expandConditionalExpectedValues($val) as $normalizedVal) {
                    if ($normalizedVal !== null && $normalizedVal !== '' && !in_array($normalizedVal, $expectedValues, true)) {
                        $expectedValues[] = $normalizedVal;
                    }
                }
            }

            if (empty($expectedValues) && !empty($values)) {
                \Log::warning("Conditional logic values parsing issue", [
                    'field' => $field->slug,
                    'dependent_field' => $fieldId,
                    'raw_values' => $values,
                    'parsed_expected_values' => $expectedValues
                ]);
            }

            // Handle array values (for multi-select/checkbox fields)
            // At this point, checkbox/multi_select should already be converted to array
            $dependentValueArray = null;
            if (is_array($dependentValue)) {
                $dependentValueArray = $dependentValue;
                // For array comparison, we'll check if any value matches
            } elseif (is_string($dependentValue) && str_contains($dependentValue, ',')) {
                // Handle comma-separated values for other field types
                $dependentValueArray = array_map('trim', explode(',', $dependentValue));
            }

            // Evaluate based on operator (Unicode-safe for Arabic)
            $rulePassed = false;
            switch ($operator) {
                case 'equals':
                case '==':
                    if ($dependentValueArray !== null) {
                        foreach ($dependentValueArray as $dv) {
                            foreach ($expectedValues as $ev) {
                                if ($this->conditionalLogicValuesEqual($dv, $ev)) {
                                    $rulePassed = true;
                                    break 2;
                                }
                            }
                        }
                    } else {
                        foreach ($expectedValues as $ev) {
                            if ($this->conditionalLogicValuesEqual($dependentValue, $ev)) {
                                $rulePassed = true;
                                break;
                            }
                        }
                    }
                    break;

                case 'not_equals':
                case '!=':
                    if ($dependentValueArray !== null) {
                        $rulePassed = true;
                        foreach ($dependentValueArray as $dv) {
                            foreach ($expectedValues as $ev) {
                                if ($this->conditionalLogicValuesEqual($dv, $ev)) {
                                    $rulePassed = false;
                                    break 2;
                                }
                            }
                        }
                    } else {
                        $rulePassed = true;
                        foreach ($expectedValues as $ev) {
                            if ($this->conditionalLogicValuesEqual($dependentValue, $ev)) {
                                $rulePassed = false;
                                break;
                            }
                        }
                    }
                    break;

                case 'contains':
                    $dependentValueStr = is_array($dependentValue)
                        ? implode(', ', $dependentValue)
                        : (string)($dependentValue ?? '');
                    if (!empty($dependentValueStr)) {
                        foreach ($expectedValues as $expected) {
                            $expectedStr = (string)($expected ?? '');
                            if (!empty($expectedStr) && str_contains($dependentValueStr, $expectedStr)) {
                                $rulePassed = true;
                                break;
                            }
                        }
                    }
                    break;

                case 'not_contains':
                    $dependentValueStr = is_array($dependentValue)
                        ? implode(', ', $dependentValue)
                        : (string)($dependentValue ?? '');
                    if (!empty($dependentValueStr)) {
                        $rulePassed = true;
                        foreach ($expectedValues as $expected) {
                            $expectedStr = (string)($expected ?? '');
                            if (!empty($expectedStr) && str_contains($dependentValueStr, $expectedStr)) {
                                $rulePassed = false;
                                break;
                            }
                        }
                    }
                    break;

                case 'greater_than':
                case '>':
                    if (is_numeric($dependentValue) && !empty($expectedValues)) {
                        $rulePassed = (float)$dependentValue > (float)($expectedValues[0] ?? 0);
                    }
                    break;

                case 'less_than':
                case '<':
                    if (is_numeric($dependentValue) && !empty($expectedValues)) {
                        $rulePassed = (float)$dependentValue < (float)($expectedValues[0] ?? 0);
                    }
                    break;

                default:
                    $rulePassed = true; // Unknown operator, default to showing field
                    break;
            }

            // If any rule passes, field should be shown (OR logic)
            if ($rulePassed) {
                return true;
            }
        }

        // No rules passed, field should be hidden
        return false;
    }

    protected function buildFormSubmissions(Form $form): array
    {
        $formSubmissions = [];
        $skippedFields = [];

        // Get all form fields
        $formFields = $form->fields()->orderBy('sort')->get();

        // Track which rating fields have been processed to avoid double processing
        $processedRatingFields = [];

        foreach ($formFields as $field) {
            $slug = $field->slug;
            $fieldType = $field->type;
            $value = $this->data[$slug] ?? null;

            // Normalize empty strings to null for consistent comparison
            if ($value === '') {
                $value = null;
            }

            // For rating fields, normalize the value early to prevent concatenation issues
            if ($fieldType === 'rating' && $value !== null) {
                // Convert to string and clean it immediately
                $originalValue = $value;
                $value = trim((string)$value);
                // Remove any non-numeric characters (rating should be integer only)
                $cleanedValue = preg_replace('/[^0-9]/', '', $value);

                // If it's numeric, ensure it's a clean integer string
                if (is_numeric($cleanedValue) && $cleanedValue !== '') {
                    $intValue = (int)$cleanedValue;
                    $valueStr = (string)$intValue;

                    // Get valid options from field to check against
                    $validOptions = [];
                    if ($field->options && is_array($field->options)) {
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            // Parse string format like "1,2,3,4,5"
                            $enOptions = FormField::parseOptionsString($field->options['en']);
                            $validOptions = array_map(function($opt) {
                                return trim((string)$opt);
                            }, $enOptions);
                        } elseif (is_array($field->options)) {
                            // Array format
                            foreach ($field->options as $option) {
                                if (is_array($option) && isset($option['value'])) {
                                    $validOptions[] = (string)$option['value'];
                                } elseif (is_numeric($option) || is_string($option)) {
                                    $validOptions[] = (string)$option;
                                }
                            }
                        }
                    }

                    // Check if value matches a valid option
                    if (!empty($validOptions) && in_array($valueStr, $validOptions)) {
                        $value = $valueStr; // Value is valid, use as-is
                    } else {
                        // Value doesn't match, check if it's a duplicate (e.g., "22" should be "2")
                        $foundMatch = false;

                        // Check if it's a duplicate of a valid option (e.g., 22 = 2+2)
                        foreach ($validOptions as $opt) {
                            $optInt = (int)$opt;
                            $optStr = (string)$optInt;
                            // Check if value is duplicate (22 = 2+2, 33 = 3+3, etc.)
                            if ((string)$intValue === $optStr . $optStr) {
                                $value = $optStr;
                                $foundMatch = true;
                                \Log::debug("Rating value corrected from duplicate", [
                                    'field' => $slug,
                                    'original' => $valueStr,
                                    'corrected' => $value,
                                    'valid_options' => $validOptions
                                ]);
                                break;
                            }
                        }

                        // If still no match and value is longer than expected, try first digit
                        if (!$foundMatch && strlen($valueStr) > 1) {
                            $firstDigit = substr($valueStr, 0, 1);
                            if (in_array($firstDigit, $validOptions)) {
                                $value = $firstDigit;
                                \Log::debug("Rating value corrected using first digit", [
                                    'field' => $slug,
                                    'original' => $valueStr,
                                    'corrected' => $value,
                                    'valid_options' => $validOptions
                                ]);
                            } else {
                                // If first digit doesn't match either, keep original (will fail validation later)
                                $value = $valueStr;
                            }
                        } elseif (!$foundMatch) {
                            // Single digit but doesn't match - keep as-is (will fail validation)
                            $value = $valueStr;
                        }
                    }
                    // Mark this rating field as processed to skip second processing
                    // Always store the value even if it doesn't match options (validation will catch it)
                    $processedRatingFields[$slug] = $value;
                } else {
                    // Value is not numeric (e.g. "bb", "aa") - rating scale can have text labels
                    // First try to match against field options (en/ar)
                    $validTextOptions = [];
                    if ($field->options && is_array($field->options)) {
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            $enOpts = FormField::parseOptionsString($field->options['en']);
                            $arOpts = FormField::parseOptionsString($field->options['ar']);
                            $validTextOptions = array_unique(array_merge(
                                array_map(fn($o) => trim((string)$o), $enOpts),
                                array_map(fn($o) => trim((string)$o), $arOpts)
                            ));
                        }
                    }
                    $trimmedOriginal = trim((string)$originalValue);
                    $matchedOption = null;
                    foreach ($validTextOptions as $opt) {
                        if ($opt === $trimmedOriginal || strcasecmp($opt, $trimmedOriginal) === 0) {
                            $matchedOption = $trimmedOriginal;
                            break;
                        }
                    }
                    if ($matchedOption !== null) {
                        $processedRatingFields[$slug] = $matchedOption;
                        $value = $matchedOption;
                    } else {
                        // If no text match, try to extract numeric part (e.g. "rating scale 1" -> "1")
                        if (preg_match('/(\d+)/', $originalValue, $matches)) {
                            $extractedValue = $matches[1];
                            if (is_numeric($extractedValue)) {
                                $processedRatingFields[$slug] = (string)(int)$extractedValue;
                                $value = (string)(int)$extractedValue;
                            } else {
                                $value = null;
                            }
                        } else {
                            // Keep original value so it is stored (e.g. "bb") - display can show it
                            $processedRatingFields[$slug] = $trimmedOriginal;
                            $value = $trimmedOriginal;
                        }
                    }
                }
            }

            $fieldLabel = is_array($field->label)
                ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                : ($field->label ?? $slug);

            // Check conditional logic - skip processing if field should be hidden
            $shouldShow = true;
            $conditionalReason = null;
            if ($field->conditional_logic && $field->conditional_logic_rules) {
                $shouldShow = $this->evaluateConditionalLogic($field, $this->data, $form);
                if (!$shouldShow) {
                    // Field is hidden by conditional logic, skip processing
                    // Build reason message
                    $rules = $field->conditional_logic_rules;
                    $conditions = [];
                    foreach ($rules as $rule) {
                        $fieldId = $rule['field_id'] ?? null;
                        $expectedValues = [];
                        foreach ($rule['values'] ?? [] as $val) {
                            $toAdd = [];

                            // Handle different value formats - include both en and ar for bilingual (same as evaluateConditionalLogic)
                            if (is_array($val)) {
                                if (isset($val['value'])) {
                                    $toAdd[] = $val['value'];
                                }
                                if (isset($val['en']) && $val['en'] !== null && $val['en'] !== '') {
                                    $toAdd[] = is_string($val['en']) ? trim($val['en']) : $val['en'];
                                }
                                if (isset($val['ar']) && $val['ar'] !== null && $val['ar'] !== '') {
                                    $toAdd[] = is_string($val['ar']) ? trim($val['ar']) : $val['ar'];
                                }
                                if (empty($toAdd) && isset($val[0])) {
                                    $toAdd[] = $val[0];
                                }
                            } elseif (is_string($val) || is_numeric($val)) {
                                $toAdd[] = $val;
                            }

                            foreach ($toAdd as $normalizedVal) {
                                if ($normalizedVal === null || $normalizedVal === '') {
                                    continue;
                                }
                                if (is_string($normalizedVal)) {
                                    if (str_contains($normalizedVal, ',')) {
                                        $parts = array_map('trim', explode(',', $normalizedVal));
                                        $normalizedVal = $parts[0];
                                    }
                                    $normalizedVal = trim($normalizedVal);
                                    if ($normalizedVal === '') {
                                        continue;
                                    }
                                }
                                if (!in_array($normalizedVal, $expectedValues, true)) {
                                    $expectedValues[] = $normalizedVal;
                                }
                            }
                        }
                        if ($fieldId) {
                            $dependentValue = $this->data[$fieldId] ?? null;
                            // Format dependent value for display
                            $displayValue = $dependentValue;
                            if (is_array($displayValue)) {
                                $displayValue = implode(', ', $displayValue);
                            }
                            $displayValue = $displayValue ?? 'empty';
                            $conditions[] = "{$fieldId} = " . implode(' or ', $expectedValues) . " (got: {$displayValue})";
                        }
                    }
                    $conditionalReason = "Conditional logic not met: " . implode(' OR ', $conditions);
                    $skippedFields[] = [
                        'field' => $fieldLabel,
                        'slug' => $slug,
                        'reason' => $conditionalReason
                    ];
                    continue;
                }
                // Field should be shown - ensure it's processed even if value seems empty initially
            }

            // If field should be shown but has no value and is not required, skip it
            if ($shouldShow && $value === null && !$field->required) {
                $skippedFields[] = [
                    'field' => $fieldLabel,
                    'slug' => $slug,
                    'reason' => 'Field is optional and no value provided'
                ];
                continue;
            }

            // If field should be shown but has no value and is required,
            // it should have been caught in validation, but we'll skip it here
            if ($shouldShow && $value === null && $field->required) {
                // This should have been caught in validation, but we'll skip to avoid errors
                $skippedFields[] = [
                    'field' => $fieldLabel,
                    'slug' => $slug,
                    'reason' => 'Field is required but no value provided (should have been caught in validation)'
                ];
                continue;
            }

            // Handle special fields by slug first
            if ($slug === 'track' || $slug === 'sub_track') {
                // Handle track/sub_track - can be ID or slug
                if ($value) {
                    $competitionId = $this->data['competition_id'] ?? null;

                    if ($slug === 'track') {
                        // Find track by slug or ID, and verify it belongs to the competition
                        $track = null;
                        if (is_numeric($value)) {
                            $track = Track::where('id', $value)
                                ->where('competition_id', $competitionId)
                                ->first();
                        } else {
                            $track = Track::where('slug', $value)
                                ->where('competition_id', $competitionId)
                                ->first();
                        }

                        if ($track) {
                            $value = $track->id;
                        } elseif ($competitionId) {
                            // If competition_id is set but track not found, set to null to avoid invalid data
                            $value = null;
                        } else {
                            // If no competition_id, try to find by slug/ID without competition check
                            $track = is_numeric($value)
                                ? Track::find($value)
                                : Track::where('slug', $value)->first();
                            $value = $track?->id;
                        }
                    } else { // sub_track
                        // Find subtrack by slug or ID, and verify it belongs to a track in the competition
                        $subTrack = null;
                        if (is_numeric($value)) {
                            // Cast to integer for proper ID matching
                            $subTrackId = (int) $value;

                            if ($competitionId) {
                                // First, try to find by ID with competition check using whereHas
                                $subTrack = SubTrack::where('id', $subTrackId)
                                    ->whereHas('track', function ($query) use ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    })
                                    ->first();

                                // If not found with whereHas, try finding the subtrack first, then verify manually
                                // This handles cases where whereHas might fail due to relationship issues
                                if (!$subTrack) {
                                    $subTrack = SubTrack::with('track')->find($subTrackId);
                                    if ($subTrack) {
                                        // Verify it belongs to the competition
                                        if (!$subTrack->track) {
                                            $subTrack = null; // Subtrack has no track
                                        } elseif ((int)$subTrack->track->competition_id !== (int)$competitionId) {
                                            $subTrack = null; // Subtrack belongs to different competition
                                        }
                                    }
                                }
                            } else {
                                // No competition ID, just find by ID
                                $subTrack = SubTrack::find($subTrackId);
                            }
                        } else {
                            // Normalize the input value to slug format for comparison
                            $normalizedValue = $this->normalizeToSlug($value);

                            // First, try exact slug match
                            $subTrack = SubTrack::where('slug', $value)
                                ->whereHas('track', function ($query) use ($competitionId) {
                                    if ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    }
                                })
                                ->first();

                            // If not found, try normalized slug match
                            if (!$subTrack) {
                                $subTrack = SubTrack::where('slug', $normalizedValue)
                                    ->whereHas('track', function ($query) use ($competitionId) {
                                        if ($competitionId) {
                                            $query->where('competition_id', $competitionId);
                                        }
                                    })
                                    ->first();
                            }

                            // If still not found, try matching by name (English or Arabic)
                            if (!$subTrack && $competitionId) {
                                $subTrack = SubTrack::whereHas('track', function ($query) use ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    })
                                    ->get()
                                    ->first(function ($st) use ($value, $normalizedValue) {
                                        // Check if the input matches the slug
                                        if ($st->slug === $value || $st->slug === $normalizedValue) {
                                            return true;
                                        }

                                        // Check if the input matches the name (English or Arabic)
                                        $name = $st->name;
                                        if (is_array($name)) {
                                            $nameEn = $name['en'] ?? '';
                                            $nameAr = $name['ar'] ?? '';

                                            // Check exact match
                                            if ($nameEn === $value || $nameAr === $value) {
                                                return true;
                                            }

                                            // Check normalized match
                                            $normalizedNameEn = $this->normalizeToSlug($nameEn);
                                            $normalizedNameAr = $this->normalizeToSlug($nameAr);
                                            if ($normalizedNameEn === $normalizedValue || $normalizedNameAr === $normalizedValue) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    });
                            }
                        }

                        if ($subTrack) {
                            $value = $subTrack->id;
                        } elseif ($competitionId) {
                            // If competition_id is set but subtrack not found, set to null
                            $value = null;
                        } else {
                            // If no competition_id, try to find by slug/ID without competition check
                            if (is_numeric($value)) {
                                $subTrack = SubTrack::find($value);
                            } else {
                                $normalizedValue = $this->normalizeToSlug($value);
                                $subTrack = SubTrack::where('slug', $value)
                                    ->orWhere('slug', $normalizedValue)
                                    ->first();

                                // If still not found, try matching by name
                                if (!$subTrack) {
                                    $subTrack = SubTrack::all()->first(function ($st) use ($value, $normalizedValue) {
                                        if ($st->slug === $value || $st->slug === $normalizedValue) {
                                            return true;
                                        }

                                        $name = $st->name;
                                        if (is_array($name)) {
                                            $nameEn = $name['en'] ?? '';
                                            $nameAr = $name['ar'] ?? '';

                                            if ($nameEn === $value || $nameAr === $value) {
                                                return true;
                                            }

                                            $normalizedNameEn = $this->normalizeToSlug($nameEn);
                                            $normalizedNameAr = $this->normalizeToSlug($nameAr);
                                            if ($normalizedNameEn === $normalizedValue || $normalizedNameAr === $normalizedValue) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    });
                                }
                            }
                            $value = $subTrack?->id;
                        }
                    }
                }
            }

            // Handle different field types
            switch ($fieldType) {
                case 'checkbox':
                    // Checkboxes can be comma-separated or array
                    if (is_string($value)) {
                        $value = array_map('trim', explode(',', $value));
                    }
                    // Map values to correct option values if they're labels
                    if ($field->options && is_array($field->options) && is_array($value)) {
                        $mappedValues = [];
                        // Check if options are stored as strings (en/ar format)
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            // For string format, values should match parsed options
                            $enOptions = FormField::parseOptionsString($field->options['en']);
                            $arOptions = FormField::parseOptionsString($field->options['ar']);
                            // Values already validated, keep as is
                            $mappedValues = $value;
                        } else {
                            // Array format - map labels to values
                            foreach ($value as $val) {
                                $found = false;
                                foreach ($field->options as $option) {
                                    if (is_array($option)) {
                                        if (isset($option['value']) && $option['value'] == $val) {
                                            $mappedValues[] = $option['value'];
                                            $found = true;
                                            break;
                                        }
                                        if (isset($option['label'])) {
                                            if (is_array($option['label']) && in_array($val, $option['label'])) {
                                                $mappedValues[] = $option['value'] ?? $val;
                                                $found = true;
                                                break;
                                            } elseif ($option['label'] == $val) {
                                                $mappedValues[] = $option['value'] ?? $val;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        // Check for en/ar format
                                        if (isset($option['en']) && $option['en'] == $val) {
                                            $mappedValues[] = $option['value'] ?? $val;
                                            $found = true;
                                            break;
                                        }
                                        if (isset($option['ar']) && $option['ar'] == $val) {
                                            $mappedValues[] = $option['value'] ?? $val;
                                            $found = true;
                                            break;
                                        }
                                    } elseif ($option == $val) {
                                        $mappedValues[] = $val;
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $mappedValues[] = $val; // Keep original if not found (validation already happened)
                                }
                            }
                        }
                        $value = $mappedValues;
                    }
                    break;

                case 'multi_select':
                    // Multi-select can be comma-separated
                    if (is_string($value)) {
                        $value = array_map('trim', explode(',', $value));
                    }
                    // Map values to correct option values if they're labels
                    if ($field->options && is_array($field->options) && is_array($value)) {
                        $mappedValues = [];
                        // Check if options are stored as strings (en/ar format)
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            // For string format, values should match parsed options
                            $enOptions = FormField::parseOptionsString($field->options['en']);
                            $arOptions = FormField::parseOptionsString($field->options['ar']);
                            // Values already validated, keep as is
                            $mappedValues = $value;
                        } else {
                            // Array format - map labels to values
                            foreach ($value as $val) {
                                $found = false;
                                foreach ($field->options as $option) {
                                    if (is_array($option)) {
                                        if (isset($option['value']) && $option['value'] == $val) {
                                            $mappedValues[] = $option['value'];
                                            $found = true;
                                            break;
                                        }
                                        if (isset($option['label'])) {
                                            if (is_array($option['label']) && in_array($val, $option['label'])) {
                                                $mappedValues[] = $option['value'] ?? $val;
                                                $found = true;
                                                break;
                                            } elseif ($option['label'] == $val) {
                                                $mappedValues[] = $option['value'] ?? $val;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        // Check for en/ar format
                                        if (isset($option['en']) && $option['en'] == $val) {
                                            $mappedValues[] = $option['value'] ?? $val;
                                            $found = true;
                                            break;
                                        }
                                        if (isset($option['ar']) && $option['ar'] == $val) {
                                            $mappedValues[] = $option['value'] ?? $val;
                                            $found = true;
                                            break;
                                        }
                                    } elseif ($option == $val) {
                                        $mappedValues[] = $val;
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $mappedValues[] = $val; // Keep original if not found (validation already happened)
                                }
                            }
                        }
                        $value = $mappedValues;
                    }
                    break;

                case 'number':
                    $value = is_numeric($value) ? (float) $value : null;
                    break;

                case 'date':
                    // Try to parse date with multiple format support
                    if ($value && is_string($value)) {
                        $value = trim($value);
                        if ($value === '') {
                            $value = null;
                            break;
                        }

                        // Try different date formats
                        $dateFormats = [
                            'd/m/Y',      // 01/01/2024 (DD/MM/YYYY)
                            'm/d/Y',      // 01/01/2024 (MM/DD/YYYY)
                            'Y-m-d',      // 2024-01-01 (ISO format)
                            'd-m-Y',      // 01-01-2024 (DD-MM-YYYY)
                            'Y/m/d',      // 2024/01/01
                            'd.m.Y',      // 01.01.2024
                            'Y.m.d',      // 2024.01.01
                        ];

                        $parsed = false;
                        foreach ($dateFormats as $format) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat($format, $value);
                                $value = $date->format('Y-m-d');
                                $parsed = true;
                                break;
                            } catch (\Exception $e) {
                                // Try next format
                                continue;
                            }
                        }

                        // If all specific formats failed, try Carbon's flexible parser
                        if (!$parsed) {
                            try {
                                $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                            } catch (\Exception $e) {
                                // If parsing still fails, keep original value
                                // This will trigger validation error later
                                $value = $value;
                            }
                        }
                    }
                    break;

                case 'file':
                    // For file imports, we expect file paths or URLs
                    // Files should be uploaded separately or paths provided
                    if ($value && is_string($value)) {
                        // Remove domain prefix if present
                        $value = preg_replace('#^https?://[^/]+/storage/#', '', $value);
                        $value = preg_replace('#^/storage/#', '', $value);
                    }
                    break;

                case 'radio':
                case 'dropdown':
                case 'rating':
                    // Skip second processing for rating fields that were already processed
                    if ($fieldType === 'rating' && isset($processedRatingFields[$slug])) {
                        $value = $processedRatingFields[$slug];
                        break;
                    }

                    // Map value to correct option value if it's a label
                    if ($field->options && is_array($field->options)) {
                        // Check if options are stored as strings (en/ar format)
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            // For string format, value should match one of the parsed options
                            $enOptions = FormField::parseOptionsString($field->options['en']);
                            $arOptions = FormField::parseOptionsString($field->options['ar']);

                            // For rating fields with string format, ensure value is stored as the actual option value
                            // The value should already be validated and match one of the options.
                            // Normalize it to ensure it's a clean string representation
                            if ($fieldType === 'rating') {
                                // Convert to string and trim to prevent any concatenation issues
                                $value = trim((string)$value);
                                // Remove any non-numeric characters first
                                $value = preg_replace('/[^0-9]/', '', $value);

                                // If it's numeric, ensure it's a clean integer string
                                // Also validate it matches one of the parsed options to prevent invalid values
                                if (is_numeric($value) && $value !== '') {
                                    $intValue = (int)$value;

                                    // Check if the value exists in the parsed options
                                    $valueStr = (string)$intValue;
                                    if (in_array($valueStr, $enOptions) || in_array($valueStr, $arOptions)) {
                                        $value = $valueStr;
                                    } else {
                                        // If value doesn't match, check if it's a duplicate (e.g., "22" should be "2")
                                        $foundMatch = false;
                                        foreach ($enOptions as $opt) {
                                            $optInt = (int)$opt;
                                            // Check if the value is a duplicate of a valid option (e.g., 22 = 2+2)
                                            if ((string)$intValue === (string)$optInt . (string)$optInt) {
                                                $value = (string)$optInt;
                                                $foundMatch = true;
                                                \Log::warning("Rating value appears to be duplicated, correcting", [
                                                    'field' => $slug,
                                                    'original_value' => $intValue,
                                                    'corrected_value' => $value
                                                ]);
                                                break;
                                            }
                                        }

                                        // If still no match and value is longer than expected, try first digit
                                        if (!$foundMatch && strlen($valueStr) > 1) {
                                            $firstDigit = (int)substr($valueStr, 0, 1);
                                            $firstDigitStr = (string)$firstDigit;
                                            if (in_array($firstDigitStr, $enOptions) || in_array($firstDigitStr, $arOptions)) {
                                                $value = $firstDigitStr;
                                                \Log::warning("Rating value too long, using first digit", [
                                                    'field' => $slug,
                                                    'original_value' => $intValue,
                                                    'corrected_value' => $value
                                                ]);
                                            } else {
                                                // If still no match, log warning and use the value as-is
                                                \Log::warning("Rating value doesn't match options", [
                                                    'field' => $slug,
                                                    'value' => $value,
                                                    'options' => $enOptions
                                                ]);
                                                $value = $valueStr;
                                            }
                                        } else {
                                            // If still no match, log warning and use the value as-is
                                            \Log::warning("Rating value doesn't match options", [
                                                'field' => $slug,
                                                'value' => $value,
                                                'options' => $enOptions
                                            ]);
                                            $value = $valueStr;
                                        }
                                    }
                                } else {
                                    $value = null; // Invalid value, set to null
                                }
                            }
                        } else {
                            // Array format - try to map label to value
                            $validOptions = [];
                            foreach ($field->options as $option) {
                                if (is_array($option) && isset($option['value'])) {
                                    $validOptions[] = $option['value'];
                                }
                            }

                            if (!in_array($value, $validOptions)) {
                                // Try to find by label (en or ar)
                                $found = false;
                                foreach ($field->options as $option) {
                                    if (is_array($option)) {
                                        if (isset($option['label'])) {
                                            if (is_array($option['label'])) {
                                                if (in_array($value, $option['label'])) {
                                                    $value = $option['value'] ?? $value;
                                                    $found = true;
                                                    break;
                                                }
                                            } elseif ($option['label'] == $value) {
                                                $value = $option['value'] ?? $value;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        // Check for en/ar format
                                        if (isset($option['en']) && $option['en'] == $value) {
                                            $value = $option['value'] ?? $value;
                                            $found = true;
                                            break;
                                        }
                                        if (isset($option['ar']) && $option['ar'] == $value) {
                                            $value = $option['value'] ?? $value;
                                            $found = true;
                                            break;
                                        }
                                    }
                                }
                                // If not found, value will remain as is (validation already happened in validateFormFields)
                            }
                        }
                    }
                    break;

                default:
                    // For text, textarea, email, phone, url, etc.
                    $value = is_string($value) ? trim($value) : $value;
                    break;
            }

            // Store the value using the field slug
            // If field should be shown and has a value, include it
            // Handle both scalar values and arrays (for checkbox/multi_select)
            $hasValue = false;
            if (is_array($value)) {
                $hasValue = !empty($value); // Array has value if it's not empty
            } else {
                // For rating fields, "0" is a valid value, so check more carefully
                if ($fieldType === 'rating') {
                    // For rating fields, only null and empty string are considered empty
                    // "0" is a valid rating value
                    $hasValue = ($value !== null && $value !== '');
                } else {
                    $hasValue = ($value !== null && $value !== ''); // Scalar has value if not null/empty
                }
            }

            if ($shouldShow && $hasValue) {
                // Ensure rating values are stored as strings for consistency
                if ($fieldType === 'rating' && $value !== null) {
                    $formSubmissions[$slug] = (string)$value;
                } else {
                    $formSubmissions[$slug] = $value;
                }
            } elseif ($shouldShow && !$field->required && !$hasValue) {
                // Optional field that should be shown but has no value - don't include it
                // (already logged in skippedFields)
            } elseif ($shouldShow && $field->required && !$hasValue) {
                // Required field that should be shown but has no value
                // This should have been caught in validation, but log it
                $skippedFields[] = [
                    'field' => $fieldLabel,
                    'slug' => $slug,
                    'reason' => 'Required field should be shown but has no value (validation should have caught this)'
                ];
            }
        }

        // Store skipped fields for potential reporting
        $this->skippedFields = array_merge($this->skippedFields, $skippedFields);

        return $formSubmissions;
    }

    public function beforeFill()
    {
        // Remove email field - it's used for participant lookup but shouldn't be saved to CompetitionApplication
        // The email is already associated via participant_id
        // Remove track and sub_track - they're stored in form_submissions, not as direct columns
        // Remove participant_name - it's used when creating the participant, not as form data
        Arr::forget($this->data, ['email', 'track', 'sub_track', 'participant_name']);
    }

    public function afterCreate()
    {
        // Send notification to participant
        if ($this->record && $this->record->type === 'submission') {
            $participant = $this->record->participant;
            if ($participant) {
                $participant->notify(new CompetitionRegistration($this->record));
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        // Get failed rows count - try both methods to ensure accuracy
        $failedRowsCount = $import->getFailedRowsCount();

        // If getFailedRowsCount() returns 0 but we suspect there are failures,
        // check the relationship directly
        if ($failedRowsCount == 0 && method_exists($import, 'failedRows')) {
            $failedRowsCount = $import->failedRows()->count();
        }

        // Check if there are any warnings in the logs about skipped fields
        // Note: We can't access instance data from static method, but we can mention
        // that admins should check logs for skipped fields
        $body = '';

        if ($failedRowsCount > 0) {
            $body = '⚠️ Import completed with errors: ';
            $body .= number_format($import->successful_rows) . ' succeeded, ';
            $body .= number_format($failedRowsCount) . ' failed. ';
            $body .= 'Download error report for details.';
        } else {
            $body = '✅ Import completed: ';
            $body .= number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported successfully.';
        }

        // Add note about checking logs for skipped fields
        // (We can't access instance data here, but we can remind admins to check logs)
        $body .= ' Note: Some fields may have been skipped due to conditional logic or validation. Check application logs for details.';

        return $body;
    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addMinute();
    }

    /**
     * Get information about fields that were skipped during import
     */
    public function getSkippedFields(): array
    {
        return $this->skippedFields;
    }

    /**
     * Extract field options for examples in CSV
     * Supports both string format (en/ar) and array format
     */
    protected static function extractFieldOptions(FormField $field): array
    {
        if (!$field->options || !is_array($field->options)) {
            return [];
        }

        $examples = [];

        // Check if options are stored as strings (en/ar format)
        if (isset($field->options['en']) && isset($field->options['ar']) &&
            is_string($field->options['en']) && is_string($field->options['ar'])) {
            // Parse string format options
            $enOptions = FormField::parseOptionsString($field->options['en']);
            $arOptions = FormField::parseOptionsString($field->options['ar']);

            // Use English options as examples (or Arabic if English is empty)
            $examples = !empty($enOptions) ? $enOptions : $arOptions;
        }
        // Check if options are in array format
        elseif (is_array($field->options) && !empty($field->options)) {
            // Check if first element has 'value' key (structured format)
            if (isset($field->options[0]) && is_array($field->options[0])) {
                if (isset($field->options[0]['value'])) {
                    // Extract values from structured format
                    $examples = collect($field->options)
                        ->pluck('value')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }
                // Check if it's array format with label only (en/ar)
                elseif (isset($field->options[0]['en']) || isset($field->options[0]['ar'])) {
                    // Extract English labels first, fallback to Arabic
                    $examples = collect($field->options)
                        ->map(function ($option) {
                            if (is_array($option)) {
                                return $option['en'] ?? $option['ar'] ?? null;
                            }
                            return $option;
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }
                // Check if it's array format with 'label' key (translatable)
                elseif (isset($field->options[0]['label'])) {
                    $examples = collect($field->options)
                        ->map(function ($option) {
                            if (is_array($option) && isset($option['label'])) {
                                if (is_array($option['label'])) {
                                    return $option['label']['en'] ?? $option['label']['ar'] ?? null;
                                }
                                return $option['label'];
                            }
                            return null;
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }
            }
            // If options is a simple indexed array
            elseif (isset($field->options[0]) && !is_array($field->options[0])) {
                $examples = array_values(array_filter($field->options));
            }
        }

        return $examples;
    }

    /**
     * Normalize a string to slug format (matching the toSnakeCase method in SubTrack model)
     */
    protected function normalizeToSlug(string $value): string
    {
        $value = preg_replace('/\s+/', '_', trim($value));               // spaces to underscores
        $value = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);     // camelCase to snake_case
        return strtolower($value);
    }
}
