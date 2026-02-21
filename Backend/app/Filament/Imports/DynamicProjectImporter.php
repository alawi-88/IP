<?php

namespace App\Filament\Imports;

use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Project;
use App\Models\Stage;
use App\Exceptions\ImportValidationException;
use App\Notifications\ProjectSubmitted;
use App\Notifications\ProjectStatusUpdated;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Arr;
use Carbon\CarbonInterface;

class DynamicProjectImporter extends Importer
{
    protected static ?string $model = Project::class;

    // Store current program ID to use in resolveRecord()
    protected static ?int $storedProgramId = null;

    public static function getColumns(): array
    {
        // Get current program from session
        // Note: getColumns() may be called from queue job where session is not available
        $sessionValue1 = null;
        $sessionValue2 = null;

        try {
            $sessionValue1 = session('current_program_id');
        } catch (\Exception $e) {
            // Session not available (e.g., in queue job)
        }

        try {
            if (request()->hasSession()) {
                $sessionValue2 = request()->session()->get('current_program_id');
            }
        } catch (\Exception $e) {
            // Request session not available
        }

        $helperValue = function_exists('currentProgramId') ? currentProgramId() : null;

        $currentProgramId = $sessionValue1;

        // Try multiple methods to get program ID
        if (!$currentProgramId) {
            $currentProgramId = $sessionValue2;
        }

        if (!$currentProgramId && function_exists('currentProgramId')) {
            $currentProgramId = $helperValue;
        }

        // Store it in static property for use in resolveRecord()
        static::$storedProgramId = $currentProgramId ? (int) $currentProgramId : null;

        // Get form_id from current stage (project-submission)
        $currentFormId = null;
        $projectForms = collect();

        if ($currentProgramId) {
            $program = Program::find($currentProgramId);
            if ($program) {
                // First try to get the current active stage
                $currentStage = $program->currentStage();
                if ($currentStage && $currentStage->slug === 'project-submission' && $currentStage->form_id) {
                    $currentFormId = $currentStage->form_id;
                    $form = Form::where('id', $currentFormId)
                        ->projectType()
                        ->published()
                        ->active()
                        ->first();
                    if ($form) {
                        $projectForms = collect([$form]);
                    }
                } else {
                    // If no active stage, try to get project-submission stage regardless of dates
                    $projectStage = $program->projectStage();
                    if ($projectStage && $projectStage->form_id) {
                        $currentFormId = $projectStage->form_id;
                        $form = Form::where('id', $currentFormId)
                            ->projectType()
                            ->published()
                            ->active()
                            ->first();
                        if ($form) {
                            $projectForms = collect([$form]);
                        }
                    }
                }
            }
        }

        // If no form found from stage, get all project forms for the program
        if ($projectForms->isEmpty() && $currentProgramId) {
            $projectForms = Form::projectType()
                ->published()
                ->active()
                ->where('program_id', $currentProgramId)
                ->get();
        }

        // Get existing projects to extract example data
        $existingProjects = collect();
        if ($currentFormId) {
            $existingProjects = Project::where('form_id', $currentFormId)
                ->where('is_archived', false)
                ->take(10)
                ->get();
        } elseif ($currentProgramId) {
            $existingProjects = Project::where('program_id', $currentProgramId)
                ->where('is_archived', false)
                ->take(10)
                ->get();
        }

        // Extract example data from existing projects
        $exampleDataByField = [];
        foreach ($existingProjects as $project) {
            if ($project->form_submissions) {
                $formSubmissions = $project->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes
                    ? $project->form_submissions->toArray()
                    : (array) $project->form_submissions;

                if (is_array($formSubmissions)) {
                    foreach ($formSubmissions as $key => $value) {
                        if ($value === null) {
                            continue;
                        }

                        if (!isset($exampleDataByField[$key])) {
                            $exampleDataByField[$key] = [];
                        }

                        if (is_array($value)) {
                            $exampleDataByField[$key][] = json_encode($value, JSON_UNESCAPED_UNICODE);
                        } elseif (is_bool($value)) {
                            $exampleDataByField[$key][] = $value ? '1' : '0';
                        } else {
                            $exampleDataByField[$key][] = (string) $value;
                        }
                    }
                }
            }
        }

        // Get participant emails - filter by current program if available
        $applicationQuery = ProgramApplication::where('is_archived', false)
            ->with(['participant', 'team.members.participant']);

        if ($currentProgramId) {
            $applicationQuery->where('program_id', $currentProgramId);
        }

        $applications = $applicationQuery->take(20)->get();
        $emailExamples = [];

        foreach ($applications as $application) {
            // For team applications, use team leader's email
            if ($application->has_team && $application->team) {
                // Check if members are already loaded (eager loaded)
                if ($application->team->relationLoaded('members')) {
                    $leaderMember = $application->team->members->where('is_leader', true)->first();
                } else {
                    $leaderMember = $application->team->members()->where('is_leader', true)->first();
                }
                if ($leaderMember && $leaderMember->participant) {
                    $emailExamples[] = $leaderMember->participant->email;
                }
            } else {
                // For individual applications, use participant's email
                if ($application->participant) {
                    $emailExamples[] = $application->participant->email;
                }
            }
        }

        // Remove duplicates and limit to 10 examples
        $emailExamples = array_unique($emailExamples);
        $emailExamples = array_slice($emailExamples, 0, 10);

        // Add program_id column to CSV example file
        $programIdExamples = $currentProgramId ? [(string) $currentProgramId] : [];

        $columns = [
            ImportColumn::make('program_id')
                ->label('Program ID')
                ->examples($programIdExamples ?: ['127'])
                ->rules(['nullable', 'integer', 'exists:programs,id']),

            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->examples($emailExamples ?: ['example@email.com'])
                ->rules(['required', 'email']),

            ImportColumn::make('status')
                ->label('Status')
                ->examples(['pending', 'qualified', 'not_qualified'])
                ->rules(['nullable', 'in:pending,qualified,not_qualified']),
        ];

        // Collect all unique form fields from all project forms
        $allFormFields = collect();

        foreach ($projectForms as $form) {
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

        // Add form fields as import columns
        // Exclude fields that conflict with required columns
        $excludedSlugs = ['email', 'status'];

        // Check if project_name exists in form fields
        $hasProjectNameField = $allFormFields->has('project_name');

        foreach ($allFormFields as $slug => $field) {
            // Skip if slug conflicts with required columns
            if (in_array($slug, $excludedSlugs)) {
                continue;
            }

            $label = is_array($field->label)
                ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                : ($field->label ?? $slug);

            // Build validation rules based on field type and requirements
            $rules = [];

            // Add required/nullable rule
            // If field has conditional logic, make it nullable/sometimes at column level
            // because conditional logic will be evaluated later in validateFormFields
            // where required validation will happen only if the condition is met
            if ($field->required && !($field->conditional_logic && $field->conditional_logic_rules)) {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }

            // Add type-specific validation rules
            switch ($field->type) {
                case 'email':
                    $rules[] = 'email';
                    break;
                case 'number':
                    $rules[] = 'numeric';
                    break;
                case 'url':
                    $rules[] = 'url';
                    break;
                case 'date':
                    // Date validation will be handled in validateFormFields
                    break;
            }

            $column = ImportColumn::make($slug)
                ->label($label)
                ->rules($rules);

            // Option-based fields: always use labels from field options (never raw IDs from existing data)
            $optionBasedTypes = ['dropdown', 'radio', 'rating', 'checkbox', 'multi_select'];
            if (in_array($field->type, $optionBasedTypes)) {
                $examples = static::extractFieldOptions($field);
                if (!empty($examples)) {
                    $column->examples($examples);
                }
            } else {
                // Add example data from existing projects if available (non-option fields)
                if (isset($exampleDataByField[$slug]) && !empty($exampleDataByField[$slug])) {
                    $fieldExamples = array_unique($exampleDataByField[$slug]);
                    $column->examples(array_slice($fieldExamples, 0, 10));
                }
            }

            // Add examples based on field type (only if no examples set yet)
            if (empty($column->getExamples())) {
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
            }

            $columns[] = $column;
        }

        // Always add project_name column if it doesn't exist in form fields
        // This ensures project_name is always available in the CSV
        if (!$hasProjectNameField) {
            $projectNameColumn = ImportColumn::make('project_name')
                ->label('Project Name')
                ->rules(['required']);

            // Add example data from existing projects if available
            if (isset($exampleDataByField['project_name']) && !empty($exampleDataByField['project_name'])) {
                $projectNameExamples = array_unique($exampleDataByField['project_name']);
                $projectNameColumn->examples(array_slice($projectNameExamples, 0, 10));
            } else {
                $projectNameColumn->examples(['My Project', 'Innovation Project', 'Tech Solution']);
            }

            $columns[] = $projectNameColumn;
        }

        // Check if track/subtrack changes are allowed and add those columns
        // Only add if they don't already exist as form fields
        $allowTrackChange = false;
        if ($currentFormId) {
            $projectFormConfig = \App\Models\ProjectFormConfig::where('form_id', $currentFormId)
                ->where('is_archived', false)
                ->first();

            if ($projectFormConfig && $projectFormConfig->allow_track_change) {
                $allowTrackChange = true;
            }
        }

        // Add track and subtrack columns if track changes are allowed and they don't exist as form fields
        if ($allowTrackChange && $currentProgramId) {
            // Check if track exists as a form field
            $hasTrackField = $allFormFields->has('track');

            if (!$hasTrackField) {
                // Always use program tracks with display names (labels) for examples - never raw IDs
                $trackExamples = [];
                $tracks = \App\Models\Track::where('program_id', $currentProgramId)->get();
                foreach ($tracks as $track) {
                    $label = static::getTrackDisplayName($track);
                    if ($label !== '') {
                        $trackExamples[] = $label;
                    }
                }
                $trackExamples = array_values(array_unique($trackExamples));

                $trackColumn = ImportColumn::make('track')
                    ->label('Track')
                    ->rules(['nullable', 'string'])
                    ->examples($trackExamples ?: ['example_track']);

                $columns[] = $trackColumn;
            }

            // Check if subtrack exists as a form field
            $hasSubtrackField = $allFormFields->has('sub_track');

            if (!$hasSubtrackField) {
                // Always use program subtracks with display names (labels) for examples - never raw IDs
                $subtrackExamples = [];
                $subtracks = \App\Models\SubTrack::whereHas('track', function ($query) use ($currentProgramId) {
                    $query->where('program_id', $currentProgramId);
                })->get();
                foreach ($subtracks as $subtrack) {
                    $label = static::getSubTrackDisplayName($subtrack);
                    if ($label !== '') {
                        $subtrackExamples[] = $label;
                    }
                }
                $subtrackExamples = array_values(array_unique($subtrackExamples));

                $subtrackColumn = ImportColumn::make('sub_track')
                    ->label('Sub Track')
                    ->rules(['nullable', 'string'])
                    ->examples($subtrackExamples ?: ['example_subtrack']);

                $columns[] = $subtrackColumn;
            }
        }

        return $columns;
    }

    public function resolveRecord(): ?Project
    {
        // Don't flush event listeners - we need boot events to run for proper saving
        // Project::flushEventListeners();

        // Get program_id from CSV data FIRST (highest priority)
        $programIdFromData = null;
        if (isset($this->data['program_id']) && $this->data['program_id'] !== '') {
            $programIdFromData = (int) $this->data['program_id'];
        }

        $email = $this->data['email'] ?? null;

        if (!$email) {
            return null;
        }

        // Normalize email to lowercase for case-insensitive lookup
        $normalizedEmail = strtolower(trim($email));

        // Get current program ID - CSV data has highest priority
        $currentProgramId = null;

        // Method 1: Use program_id from CSV data (HIGHEST PRIORITY)
        if ($programIdFromData) {
            $currentProgramId = $programIdFromData;
        }

        // Method 2: Use stored program ID from getColumns() (if CSV doesn't have it)
        if (!$currentProgramId && static::$storedProgramId) {
            $currentProgramId = static::$storedProgramId;
        }

        // Method 3: Try session (fallback only if CSV and stored value are not available)
        if (!$currentProgramId) {
            try {
                $sessionValue = session('current_program_id');
                if ($sessionValue) {
                    $currentProgramId = (int) $sessionValue;
                }
            } catch (\Exception $e) {
                // Session not available (e.g., in queue job)
            }
        }

        // Method 4: Try request session (fallback)
        if (!$currentProgramId) {
            try {
                if (request()->hasSession()) {
                    $sessionValue = request()->session()->get('current_program_id');
                    if ($sessionValue) {
                        $currentProgramId = (int) $sessionValue;
                    }
                }
            } catch (\Exception $e) {
                // Request session not available
            }
        }

        // Method 5: Try helper function if available (fallback)
        if (!$currentProgramId && function_exists('currentProgramId')) {
            $helperValue = currentProgramId();
            if ($helperValue) {
                $currentProgramId = (int) $helperValue;
            }
        }

        if (!$currentProgramId) {
            throw ImportValidationException::withMessages([
                'email' => "No program is currently selected. Please select a program before importing projects.",
            ]);
        }


        // First, check if the email belongs to a team member (not leader) to provide a clear error message
        $teamMemberApplication = ProgramApplication::where('is_archived', false)
            ->where('program_id', $currentProgramId) // Filter by current program
            ->where('has_team', true)
            ->whereHas('team.members', function ($query) use ($normalizedEmail) {
                $query->where('is_leader', false)
                    ->whereHas('participant', function ($q) use ($normalizedEmail) {
                        $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    });
            })
            ->with(['team.members' => function ($query) {
                $query->where('is_leader', true)->with('participant');
            }])
            ->first();

        if ($teamMemberApplication) {
            // Get the team leader's email for the error message
            $teamLeader = $teamMemberApplication->team->members->where('is_leader', true)->first();
            $leaderEmail = $teamLeader && $teamLeader->participant ? $teamLeader->participant->email : 'N/A';

            throw ImportValidationException::withMessages([
                'email' => "Only the team leader can upload projects. The email '{$email}' belongs to a team member, not the team leader. Please use the team leader's email: {$leaderEmail}",
            ]);
        }

        // First, try to find application by participant email (for individual applications)
        // IMPORTANT: Filter by current program to ensure we get the correct application
        $application = ProgramApplication::where('is_archived', false)
            ->where('program_id', $currentProgramId) // Filter by current program
            ->whereHas('participant', function ($query) use ($normalizedEmail) {
                $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
            })
            ->first();


        // If not found, try to find by team leader's email (for team applications)
        if (!$application) {
            $application = ProgramApplication::where('is_archived', false)
                ->where('program_id', $currentProgramId) // Filter by current program
                ->where('has_team', true)
                ->whereHas('team.members', function ($query) use ($normalizedEmail) {
                    $query->where('is_leader', true)
                        ->whereHas('participant', function ($q) use ($normalizedEmail) {
                            $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                        });
                })
                ->first();

        }

        if (!$application) {
            // Check if participant exists but has no application
            $participant = \App\Models\Participant::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            $participantExists = $participant !== null;

            // Check if there are any applications (including archived) for this email
            $anyApplication = ProgramApplication::whereHas('participant', function ($query) use ($normalizedEmail) {
                $query->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
            })->orWhereHas('team.members', function ($query) use ($normalizedEmail) {
                $query->where('is_leader', true)
                    ->whereHas('participant', function ($q) use ($normalizedEmail) {
                        $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    });
            })->first();

            $hasArchivedApplication = $anyApplication !== null;

            $errorMessage = "No active application found for email: {$email}.";
            if ($participantExists && !$hasArchivedApplication) {
                $errorMessage .= " Participant exists but has no application for this program.";
            } elseif ($hasArchivedApplication) {
                $errorMessage .= " Application exists but is archived. Please restore the application first.";
            } else {
                $errorMessage .= " Please ensure the email belongs to either a participant or a team leader with an active application.";
            }

            throw ImportValidationException::withMessages([
                'email' => $errorMessage,
            ]);
        }

        // Validate that the email belongs to the team leader if this is a team application
        if ($application->has_team && $application->team) {
            // Load team members if not already loaded
            if (!$application->team->relationLoaded('members')) {
                $application->team->load('members.participant');
            }

            // Find the team leader
            $teamLeader = $application->team->members->where('is_leader', true)->first();

            if (!$teamLeader || !$teamLeader->participant) {
                throw ImportValidationException::withMessages([
                    'email' => "Team leader not found for the application associated with email: {$email}.",
                ]);
            }

            // Check if the email belongs to the team leader
            $leaderEmail = strtolower(trim($teamLeader->participant->email));
            if ($leaderEmail !== $normalizedEmail) {
                // Check if the email belongs to any team member (not the leader)
                $isTeamMember = $application->team->members->contains(function ($member) use ($normalizedEmail) {
                    return $member->participant &&
                           strtolower(trim($member->participant->email)) === $normalizedEmail &&
                           !$member->is_leader;
                });

                if ($isTeamMember) {
                    throw ImportValidationException::withMessages([
                        'email' => "Only the team leader can upload projects. The email '{$email}' belongs to a team member, not the team leader. Please use the team leader's email: {$teamLeader->participant->email}",
                    ]);
                } else {
                    throw ImportValidationException::withMessages([
                        'email' => "The email '{$email}' does not belong to the team leader for this application. Please use the team leader's email: {$teamLeader->participant->email}",
                    ]);
                }
            }
        } else {
            // For individual applications, verify the email belongs to the participant
            if (!$application->participant) {
                throw ImportValidationException::withMessages([
                    'email' => "Participant not found for the application associated with email: {$email}.",
                ]);
            }

            $participantEmail = strtolower(trim($application->participant->email));
            if ($participantEmail !== $normalizedEmail) {
                throw ImportValidationException::withMessages([
                    'email' => "The email '{$email}' does not match the participant's email for this application. Please use the correct email: {$application->participant->email}",
                ]);
            }
        }

        // Verify that the application belongs to the current program
        if ((int)$application->program_id !== (int)$currentProgramId) {
            throw ImportValidationException::withMessages([
                'email' => "The application for email '{$email}' belongs to program ID {$application->program_id}, but the current selected program is ID {$currentProgramId}. Please ensure the imported project belongs to the currently selected program.",
            ]);
        }

        // Verify that the application is approved before allowing project import
        if ($application->status !== 'approved') {
            $statusLabel = match($application->status) {
                'pending' => 'pending',
                'rejected' => 'rejected',
                default => $application->status,
            };

            throw ImportValidationException::withMessages([
                'email' => "The application for email '{$email}' has a status of '{$statusLabel}' and must be approved before projects can be imported. Please ensure the application is approved first.",
            ]);
        }

        $programId = $currentProgramId; // Use current program ID instead of application's program_id

        // Get form_id from project-submission stage
        $formId = null;
        $program = Program::find($programId);

        if ($program) {
            // First try to get the current active stage
            $currentStage = $program->currentStage();
            if ($currentStage && $currentStage->slug === 'project-submission' && $currentStage->form_id) {
                $formId = $currentStage->form_id;
            } else {
                // If no active stage, try to get project-submission stage regardless of dates
                $projectStage = $program->projectStage();
                if ($projectStage && $projectStage->form_id) {
                    $formId = $projectStage->form_id;
                } else {
                    // If still no form_id, try to get any project form for this program
                    $projectForm = Form::where('program_id', $programId)
                        ->projectType()
                        ->published()
                        ->active()
                        ->first();
                    if ($projectForm) {
                        $formId = $projectForm->id;
                    }
                }
            }
        }

        if (!$formId) {
            throw ImportValidationException::withMessages([
                'email' => "No project form found for program ID: {$programId}. Please ensure a project-submission stage exists with a form assigned, or a project form is configured for this program.",
            ]);
        }

        // Get the form
        $form = Form::where('id', $formId)
            ->projectType()
            ->published()
            ->active()
            ->first();

        if (!$form) {
            throw ImportValidationException::withMessages([
                'email' => "No project form found for form ID: {$formId}.",
            ]);
        }

        // Verify the form belongs to the same program as the application
        if ($form->program_id !== $application->program_id) {
            throw ImportValidationException::withMessages([
                'form_id' => "Form ID {$formId} does not belong to the same program as the application for email: {$email}",
            ]);
        }

        // Get application_id for the found application
        $applicationId = $application->id;

        // Check if project already exists for this application and form (including archived)
        $existingProject = Project::where('form_id', $formId)
            ->where('application_id', $applicationId)
            ->first(); // Check all projects, not just non-archived ones

        // Validate all form fields before building form_submissions
        $this->validateFormFields($form);

        // Build form_submissions from dynamic fields
        $formSubmissions = $this->buildFormSubmissions($form);

        if ($existingProject) {
            // If project is archived, restore it first
            if ($existingProject->is_archived) {
                $existingProject->restore();
            }

            // Store old status for notification
            $oldStatus = $existingProject->status;
            $newStatus = $this->data['status'] ?? $existingProject->status;

            // Update existing project
            $existingProject->form_submissions = $formSubmissions;
            $existingProject->status = $newStatus;
            $existingProject->type = 'submission'; // Always set to submission for imports
            $existingProject->is_archived = false; // Ensure it's not archived
            // Don't reset evaluation_status and total_score for existing projects
            $existingProject->save(); // Save the changes

            // Store old status for afterCreate notification
            $this->oldStatus = $oldStatus;
            $this->isUpdate = true;

            return $existingProject;
        }

        // Mark as new project for afterCreate notification
        $this->isUpdate = false;

        // Create and save the project directly
        // We save it here because Filament's automatic save might not work reliably
        try {
            $project = Project::create([
                'form_id' => $formId,
                'application_id' => $applicationId,
                'program_id' => $programId, // Use current program ID
                'form_submissions' => $formSubmissions,
                'status' => $this->data['status'] ?? 'pending',
                'type' => 'submission',
                'is_archived' => false,
                'evaluation_status' => false,
                'total_score' => 0,
            ]);

            return $project;
        } catch (\Exception $e) {
            \Log::error('Failed to create project during import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'form_id' => $formId,
                'application_id' => $applicationId,
            ]);
            throw $e;
        }
    }

    /**
     * Ensure a regex pattern has valid PCRE delimiters for preg_match.
     * Patterns from DB/config may be stored without delimiters (e.g. ^[a-z]+$).
     *
     * @return string|null The pattern with delimiters, or null if invalid
     */
    protected function ensurePregDelimiters(string $pattern): ?string
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return null;
        }
        // Already delimited: starts with a non-alphanumeric delimiter and ends with same (optional modifiers allowed)
        if (preg_match('#^([^\w\s\\\\]).*\1[imsxADSUXJu]*$#', $pattern)) {
            return $pattern;
        }
        // Wrap in a delimiter that does not appear in the pattern to avoid escaping
        $delimiter = strpos($pattern, '#') === false ? '#' : '~';
        return $delimiter . $pattern . $delimiter;
    }

    protected function validateFormFields(Form $form): void
    {
        $formFields = $form->fields()->orderBy('sort')->get();
        $errors = [];

        foreach ($formFields as $field) {
            $slug = $field->slug;
            $fieldType = $field->type;
            $value = $this->data[$slug] ?? null;

            // Normalize empty values (trim whitespace and check if truly empty)
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }

            // Check if value is truly empty (null, empty string, or whitespace-only)
            $isEmpty = ($value === null || $value === '' || (is_string($value) && trim($value) === ''));

            // Check conditional logic - skip ALL validation (including "required")
            // if the field should be hidden based on current data.
            $shouldShow = true;
            if ($field->conditional_logic && $field->conditional_logic_rules) {
                $shouldShow = $this->evaluateConditionalLogic($field, $this->data, $form);
                if (!$shouldShow) {
                    // Field is hidden by conditional logic, skip validation
                    // But if field has a value, it means the data doesn't match the condition
                    // This is an error - the value was provided but condition not met

                    // Check if value exists (handle various empty cases)
                    $hasValue = false;
                    if (!$isEmpty) {
                        // Also check for empty arrays
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

            // Check if field exists in the imported data
            $fieldExistsInData = array_key_exists($slug, $this->data);

            // Validate required fields - only when the field SHOULD be shown.
            // Hidden fields (by conditional logic) are never treated as required during import.
            //
            // IMPORTANT (import-specific behavior):
            // For fields that have conditional logic configured, we NEVER enforce "required"
            // during import, even if the condition is evaluated as met.
            // This avoids blocking imports with "is required" errors for conditional fields
            // like multi-select dropdowns when their visibility depends on other answers.
            if ($shouldShow && $field->required && !($field->conditional_logic && $field->conditional_logic_rules)) {
                if (!$fieldExistsInData || $isEmpty) {
                    $fieldLabel = is_array($field->label)
                        ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                        : ($field->label ?? $slug);

                    if (!$fieldExistsInData) {
                        $errors[$slug] = "The field '{$fieldLabel}' is required but is missing from the CSV. Please add this column to your import file.";
                    } else {
                        $errors[$slug] = "The field '{$fieldLabel}' is required but was left empty in the CSV. Please provide a value or ensure the column is mapped correctly.";
                    }
                    continue;
                }
            }

            // Skip validation if no value and field is not required, or if field wasn't in CSV
            if ($isEmpty || !$fieldExistsInData) {
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
                        $fieldLabel = is_array($field->label)
                            ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                            : ($field->label ?? $slug);
                        $allValid = array_unique(array_merge($validOptions, $validLabels));
                        $errors[$slug] = "The value '{$value}' is not valid for field '{$fieldLabel}'. Valid options: " . implode(', ', $allValid);
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
                            $fieldLabel = is_array($field->label)
                                ? ($field->label['en'] ?? $field->label['ar'] ?? $slug)
                                : ($field->label ?? $slug);
                            $allValid = array_unique(array_merge($validOptions, $validLabels));
                            $errors[$slug] = "The value '{$val}' is not valid for field '{$fieldLabel}'. Valid options: " . implode(', ', $allValid);
                            break;
                        }
                    }
                }
            }

            // Validate custom validation rules
            if ($field->validation_rules && is_array($field->validation_rules)) {
                foreach ($field->validation_rules as $rule) {
                    // Support both 'rule' and 'type' keys for compatibility
                    $ruleType = $rule['rule'] ?? $rule['type'] ?? null;
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
                        case 'min':
                            if ($isTextField) {
                                // For text fields, 'min' means minimum length
                                if (is_string($value) && strlen($value) < (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                                }
                            } else {
                                // For number fields, 'min' means minimum value
                                if (is_numeric($value) && (float)$value < (float)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue}.";
                                }
                            }
                            break;

                        case 'max':
                            // Skip max validation for file fields - handled in validateFieldType
                            if ($fieldType === 'file') {
                                break;
                            }
                            if ($isTextField) {
                                // For text fields, 'max' means maximum length
                                if (is_string($value) && strlen($value) > (int)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                                }
                            } else {
                                // For number fields, 'max' means maximum value
                                if (is_numeric($value) && (float)$value > (float)$ruleValue) {
                                    $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue}.";
                                }
                            }
                            break;

                        case 'min_length':
                            if (is_string($value) && strlen($value) < (int)$ruleValue) {
                                $errors[$slug] = "The field '{$fieldLabel}' must be at least {$ruleValue} characters long.";
                            }
                            break;

                        case 'max_length':
                            if (is_string($value) && strlen($value) > (int)$ruleValue) {
                                $errors[$slug] = "The field '{$fieldLabel}' must not exceed {$ruleValue} characters.";
                            }
                            break;

                        case 'regex':
                        case 'pattern':
                            if (is_string($value)) {
                                $pattern = $this->ensurePregDelimiters($ruleValue);
                                if ($pattern !== null && !preg_match($pattern, $value)) {
                                    $errors[$slug] = "The field '{$fieldLabel}' format is invalid.";
                                }
                            }
                            break;

                        case 'numeric':
                            if (!is_numeric($value)) {
                                $errors[$slug] = "The field '{$fieldLabel}' must be a number.";
                            }
                            break;

                        case 'email':
                            if (is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$slug] = "The field '{$fieldLabel}' must be a valid email address.";
                            }
                            break;

                        case 'url':
                            if (is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                                $errors[$slug] = "The field '{$fieldLabel}' must be a valid URL.";
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
     * Validate field type-specific constraints
     */
    protected function validateFieldType(FormField $field, $value): ?string
    {
        $fieldLabel = is_array($field->label)
            ? ($field->label['en'] ?? $field->label['ar'] ?? $field->slug)
            : ($field->label ?? $field->slug);

        // Validate email fields
        if ($field->type === 'email' && is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The field '{$fieldLabel}' must be a valid email address.";
        }

        // Validate URL fields
        if ($field->type === 'url' && is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            return "The field '{$fieldLabel}' must be a valid URL.";
        }

        // Validate number fields
        if ($field->type === 'number' && !is_numeric($value)) {
            return "The field '{$fieldLabel}' must be a number.";
        }

        // Validate date fields - support multiple formats
        if ($field->type === 'date' && is_string($value)) {
            if ($value === '' || trim($value) === '') {
                return null; // Empty values are handled by required validation
            }

            $value = trim($value);
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
                $date = \DateTime::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    $parsed = true;
                    break;
                }
            }
            if (!$parsed) {
                try {
                    \Carbon\Carbon::parse($value);
                    $parsed = true;
                } catch (\Exception $e) {
                    // ignore
                }
            }

            if (!$parsed) {
                return "The field '{$fieldLabel}' must be a valid date. Supported formats: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD, etc.";
            }
        }

        // Validate time fields - support common 24-hour and 12-hour formats
        if ($field->type === 'time' && is_string($value)) {
            if ($value === '') {
                return null; // Empty values are handled by required validation
            }

            $timeFormats = [
                'H:i',    // 14:30 (24h)
                'H:i:s',  // 14:30:00 (24h)
                'h:i A',   // 02:30 PM (12h)
                'h:i a',   // 02:30 pm
                'g:i A',   // 2:30 PM (no leading zero)
                'g:i a',
            ];

            $parsed = false;
            foreach ($timeFormats as $format) {
                try {
                    $time = \Carbon\Carbon::createFromFormat($format, trim($value));
                    if ($time) {
                        $parsed = true;
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            if (!$parsed) {
                try {
                    \Carbon\Carbon::parse(trim($value));
                    $parsed = true;
                } catch (\Exception $e) {
                    // ignore
                }
            }

            if (!$parsed) {
                return "The field '{$fieldLabel}' must be a valid time. Supported formats: HH:MM (24h), HH:MM:SS, or 2:30 PM.";
            }
        }

        // Validate file fields - format + validation rules (extensions, max size, file existence)
        if ($field->type === 'file') {
            if (is_numeric($value)) {
                return "The field '{$fieldLabel}' must be a file path or URL, not a number.";
            }
            if (!is_string($value)) {
                return "The field '{$fieldLabel}' must be a valid file path or URL.";
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
                return "The field '{$fieldLabel}' must be a valid file path or URL.";
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
                        return "The field '{$fieldLabel}' file size ({$actualSizeMB}MB) exceeds the maximum allowed size ({$maxLimit['mb']}MB).";
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
                    return "The field '{$fieldLabel}' references a file that does not exist: {$filePath}";
                }

                // Check file size if file exists (Max File Size from validation_rules: max_file_size in MB or rule "max" value in KB)
                if ($resolvedPath && file_exists($resolvedPath)) {
                    $fileSize = filesize($resolvedPath);
                    if ($maxLimit !== null && $fileSize > $maxLimit['bytes']) {
                        $actualSizeMB = round($fileSize / 1024 / 1024, 2);
                        return "The field '{$fieldLabel}' file size ({$actualSizeMB}MB) exceeds the maximum allowed size ({$maxLimit['mb']}MB).";
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
                    return "The field '{$fieldLabel}' must be a file of type: {$list}.";
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
                    return "The field '{$fieldLabel}' is a text field and cannot accept file paths.";
                }
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

            // Normalize dependent value
            if (is_string($dependentValue)) {
                $dependentValue = trim($dependentValue);
                if ($dependentValue === '') {
                    $dependentValue = null;
                }
            } elseif ($dependentValue === '') {
                $dependentValue = null;
            }

            // Normalize expected values from conditional logic rules (bilingual: en + ar; split "en,ar" so Arabic matches)
            $normalizedExpectedValues = [];
            foreach ($values as $val) {
                foreach ($this->expandConditionalExpectedValues($val) as $normalizedVal) {
                    if ($normalizedVal !== null && $normalizedVal !== '' && !in_array($normalizedVal, $normalizedExpectedValues, true)) {
                        $normalizedExpectedValues[] = $normalizedVal;
                    }
                }
            }

            // Normalize dependent value for comparison (Unicode-safe for Arabic)
            $normalizedDependentValue = $dependentValue;
            if ($normalizedDependentValue !== null && is_string($normalizedDependentValue)) {
                $normalizedDependentValue = $this->normalizeConditionalValue($normalizedDependentValue) ?? null;
            }

            $matches = false;

            switch ($operator) {
                case 'equals':
                case '==':
                    if ($normalizedDependentValue !== null) {
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            if ($this->conditionalLogicValuesEqual($normalizedDependentValue, $expectedVal)) {
                                $matches = true;
                                break;
                            }
                        }
                    } else {
                        $matches = in_array(null, $normalizedExpectedValues, true);
                    }
                    break;

                case 'not_equals':
                case '!=':
                    $equalsMatch = false;
                    if ($normalizedDependentValue !== null) {
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            if ($this->conditionalLogicValuesEqual($normalizedDependentValue, $expectedVal)) {
                                $equalsMatch = true;
                                break;
                            }
                        }
                    } else {
                        $equalsMatch = in_array(null, $normalizedExpectedValues, true);
                    }
                    $matches = !$equalsMatch;
                    break;

                case 'contains':
                    if (is_array($normalizedDependentValue)) {
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            foreach ($normalizedDependentValue as $dv) {
                                if ($this->conditionalLogicValuesEqual($dv, $expectedVal)) {
                                    $matches = true;
                                    break 2;
                                }
                            }
                        }
                    } else {
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            if ($this->conditionalLogicValuesEqual($normalizedDependentValue, $expectedVal)) {
                                $matches = true;
                                break;
                            }
                        }
                    }
                    break;

                case 'not_contains':
                    if (is_array($normalizedDependentValue)) {
                        $containsMatch = false;
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            foreach ($normalizedDependentValue as $dv) {
                                if ($this->conditionalLogicValuesEqual($dv, $expectedVal)) {
                                    $containsMatch = true;
                                    break 2;
                                }
                            }
                        }
                        $matches = !$containsMatch;
                    } else {
                        $containsMatch = false;
                        foreach ($normalizedExpectedValues as $expectedVal) {
                            if ($this->conditionalLogicValuesEqual($normalizedDependentValue, $expectedVal)) {
                                $containsMatch = true;
                                break;
                            }
                        }
                        $matches = !$containsMatch;
                    }
                    break;
            }

            // If any rule matches, field should be shown (OR logic)
            if ($matches) {
                return true;
            }
        }

        // No rules matched, field should be hidden
        return false;
    }

    protected function buildFormSubmissions(Form $form): array
    {
        $formSubmissions = [];

        // Get all form fields
        $formFields = $form->fields()->orderBy('sort')->get();

        foreach ($formFields as $field) {
            $slug = $field->slug;
            $fieldType = $field->type;
            $value = $this->data[$slug] ?? null;

            // Skip if no value and field is not required
            if ($value === null && !$field->required) {
                continue;
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
                    // Try to parse date
                    if ($value && is_string($value)) {
                        try {
                            $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $value = $value; // Keep original if parsing fails
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
                    // Map value to correct option value if it's a label
                    if ($field->options && is_array($field->options)) {
                        // Check if options are stored as strings (en/ar format)
                        if (isset($field->options['en']) && isset($field->options['ar']) &&
                            is_string($field->options['en']) && is_string($field->options['ar'])) {
                            // For string format, value should match one of the parsed options
                            $enOptions = FormField::parseOptionsString($field->options['en']);
                            $arOptions = FormField::parseOptionsString($field->options['ar']);
                            // Value should already be validated, just keep it as is
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
            if ($value !== null && $value !== '') {
                $formSubmissions[$slug] = $value;
            }
        }

        // Add project_name to form_submissions if it exists in data but not in form fields
        // This handles the case where project_name is added as a column but not as a form field
        if (isset($this->data['project_name']) && !isset($formSubmissions['project_name'])) {
            $projectName = $this->data['project_name'];
            if ($projectName !== null && $projectName !== '') {
                $formSubmissions['project_name'] = is_string($projectName) ? trim($projectName) : $projectName;
            }
        }

        $programIdForResolve = !empty($this->data['program_id']) ? (int) $this->data['program_id'] : static::$storedProgramId;

        // Add track to form_submissions if it exists in data but not in form fields
        // Accept ID, slug, or display name (label) and store slug
        $trackModel = null;
        if (isset($this->data['track']) && !isset($formSubmissions['track'])) {
            $track = $this->data['track'];
            if ($track !== null && $track !== '') {
                $track = is_string($track) ? trim($track) : (string)$track;
                if (is_numeric($track)) {
                    $trackModel = \App\Models\Track::find((int)$track);
                } else {
                    $trackModel = \App\Models\Track::where('slug', $track)->first();
                    if (!$trackModel && $programIdForResolve) {
                        $trackModel = \App\Models\Track::where('program_id', $programIdForResolve)->get()
                            ->first(function ($t) use ($track) {
                                $label = static::getTrackDisplayName($t);
                                return strcasecmp($label, $track) === 0;
                            });
                    }
                }
                if ($trackModel && $trackModel->slug) {
                    $formSubmissions['track'] = $trackModel->slug;
                } else {
                    $formSubmissions['track'] = $track;
                }
            }
        }

        // Add sub_track to form_submissions if it exists in data but not in form fields
        // Accept ID, slug, or display name (label) and store slug
        $subTrackModel = null;
        if (isset($this->data['sub_track']) && !isset($formSubmissions['sub_track'])) {
            $subTrack = $this->data['sub_track'];
            if ($subTrack !== null && $subTrack !== '') {
                $subTrack = is_string($subTrack) ? trim($subTrack) : (string)$subTrack;
                if (is_numeric($subTrack)) {
                    $subTrackModel = \App\Models\SubTrack::find((int)$subTrack);
                } else {
                    $subTrackModel = \App\Models\SubTrack::where('slug', $subTrack)->first();
                    if (!$subTrackModel && $programIdForResolve) {
                        $subTrackModel = \App\Models\SubTrack::whereHas('track', fn($q) => $q->where('program_id', $programIdForResolve))
                            ->get()
                            ->first(function ($st) use ($subTrack) {
                                $label = static::getSubTrackDisplayName($st);
                                return strcasecmp($label, $subTrack) === 0;
                            });
                    }
                }
                if ($subTrackModel && $subTrackModel->slug) {
                    $formSubmissions['sub_track'] = $subTrackModel->slug;
                } else {
                    $formSubmissions['sub_track'] = $subTrack;
                }
            }
        }

        // If Track or Subtrack are form fields, resolve them from formSubmissions
        if (!$trackModel && isset($formSubmissions['track'])) {
            $trackValue = $formSubmissions['track'];
            if ($trackValue !== null && $trackValue !== '') {
                $trackValue = is_string($trackValue) ? trim($trackValue) : (string)$trackValue;
                if (is_numeric($trackValue)) {
                    $trackModel = \App\Models\Track::find((int)$trackValue);
                } else {
                    $trackModel = \App\Models\Track::where('slug', $trackValue)->first();
                    if (!$trackModel && $programIdForResolve) {
                        $trackModel = \App\Models\Track::where('program_id', $programIdForResolve)->get()
                            ->first(function ($t) use ($trackValue) {
                                $label = static::getTrackDisplayName($t);
                                return strcasecmp($label, $trackValue) === 0;
                            });
                    }
                }
            }
        }

        if (!$subTrackModel && isset($formSubmissions['sub_track'])) {
            $subTrackValue = $formSubmissions['sub_track'];
            if ($subTrackValue !== null && $subTrackValue !== '') {
                $subTrackValue = is_string($subTrackValue) ? trim($subTrackValue) : (string)$subTrackValue;
                if (is_numeric($subTrackValue)) {
                    $subTrackModel = \App\Models\SubTrack::find((int)$subTrackValue);
                } else {
                    $subTrackModel = \App\Models\SubTrack::where('slug', $subTrackValue)->first();
                    if (!$subTrackModel && $programIdForResolve) {
                        $subTrackModel = \App\Models\SubTrack::whereHas('track', fn($q) => $q->where('program_id', $programIdForResolve))
                            ->get()
                            ->first(function ($st) use ($subTrackValue) {
                                $label = static::getSubTrackDisplayName($st);
                                return strcasecmp($label, $subTrackValue) === 0;
                            });
                    }
                }
            }
        }

        // Validate that the selected Subtrack belongs to the chosen Track (when both are provided)
        if ($trackModel && $subTrackModel && (int) $subTrackModel->track_id !== (int) $trackModel->id) {
            $trackDisplayName = static::getTrackDisplayName($trackModel);
            $subTrackDisplayName = static::getSubTrackDisplayName($subTrackModel);
            throw ImportValidationException::withMessages([
                'sub_track' => "The selected Subtrack '{$subTrackDisplayName}' does not belong to the chosen Track '{$trackDisplayName}'. Please ensure the Subtrack belongs to the selected Track.",
            ]);
        }

        return $formSubmissions;
    }

    public function beforeFill()
    {
        // Remove email, project_name, and program_id - they're not direct columns
        // email is used to find the application but shouldn't be saved to Project
        // project_name is stored in form_submissions, not as a direct column
        // program_id is used to filter applications but shouldn't be saved to Project (it's set from application)
        // NOTE: Keep 'status' - it's a direct column and should be saved
        Arr::forget($this->data, ['email', 'project_name', 'program_id']);
    }

    public function afterCreate()
    {
        if (!$this->record) {
            return;
        }

        // Reload the project to ensure we have the latest data
        $project = $this->record->fresh();

        if (!$project) {
            return;
        }

        // Ensure all required fields are set correctly after save
        $needsUpdate = false;

        if ($project->type !== 'submission') {
            $project->type = 'submission';
            $needsUpdate = true;
        }

        if ($project->is_archived !== false) {
            $project->is_archived = false;
            $needsUpdate = true;
        }

        // Ensure program_id is set
        if (!$project->program_id && $project->application) {
            $project->program_id = $project->application->program_id;
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $project->save();
        }

        // Get the application to find participants
        $application = $project->application;
        if (!$application) {
            return;
        }

        // Determine if this is an update or new project
        $isUpdate = $this->isUpdate ?? false;
        $oldStatus = $this->oldStatus ?? null;
        $newStatus = $project->status;

        // Send notifications based on status
        if ($isUpdate && $oldStatus && $oldStatus !== $newStatus) {
            // Status changed - send status update notification
            $this->sendStatusUpdateNotification($project, $oldStatus, $newStatus, $application);
        } elseif (!$isUpdate) {
            // New project - send project submitted notification
            $this->sendProjectSubmittedNotification($project, $application);
        } elseif ($isUpdate && $oldStatus === $newStatus) {
            // Project updated but status didn't change - send status update notification anyway
            // This ensures participants are notified about the update
            $this->sendStatusUpdateNotification($project, $oldStatus, $newStatus, $application);
        }
    }

    protected function sendProjectSubmittedNotification($project, $application)
    {
        // Send notification to participant (individual or team members)
        if ($application->has_team && $application->team) {
            // Send to all team members
            foreach ($application->team->members as $member) {
                $participant = $member->participant;
                if ($participant) {
                    $participant->notify(new ProjectSubmitted($project));
                }
            }
        } else {
            // Send to individual participant
            $participant = $application->participant;
            if ($participant) {
                $participant->notify(new ProjectSubmitted($project));
            }
        }
    }

    protected function sendStatusUpdateNotification($project, $oldStatus, $newStatus, $application)
    {
        // Send notification to participant (individual or team members)
        if ($application->has_team && $application->team) {
            // Send to all team members
            foreach ($application->team->members as $member) {
                $participant = $member->participant;
                if ($participant) {
                    $participant->notify(new ProjectStatusUpdated($project, $oldStatus, $newStatus));
                }
            }
        } else {
            // Send to individual participant
            $participant = $application->participant;
            if ($participant) {
                $participant->notify(new ProjectStatusUpdated($project, $oldStatus, $newStatus));
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        // Get failed rows count
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount == 0 && method_exists($import, 'failedRows')) {
            $failedRowsCount = $import->failedRows()->count();
        }

        if ($failedRowsCount > 0) {
            $body = '⚠️ Import completed with errors: ';
            $body .= number_format($import->successful_rows) . ' succeeded, ';
            $body .= number_format($failedRowsCount) . ' failed. ';
            $body .= 'Download error report for details.';
        } else {
            $body = '✅ Import completed: ';
            $body .= number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported successfully.';
        }

        return $body;
    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addMinute();
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
                // Prefer labels over numeric values for example sheet readability
                if (isset($field->options[0]['label']) || isset($field->options[0]['en']) || isset($field->options[0]['ar'])) {
                    $examples = collect($field->options)
                        ->map(function ($option) {
                            if (!is_array($option)) {
                                return $option;
                            }
                            if (isset($option['label'])) {
                                return is_array($option['label'])
                                    ? ($option['label']['en'] ?? $option['label']['ar'] ?? null)
                                    : $option['label'];
                            }
                            return $option['en'] ?? $option['ar'] ?? null;
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }
                elseif (isset($field->options[0]['value'])) {
                    // Use value only when it looks like a label (non-numeric); avoid numeric IDs in example sheet
                    $examples = collect($field->options)
                        ->map(function ($option) {
                            $val = $option['value'] ?? null;
                            if ($val === null || $val === '') {
                                return null;
                            }
                            if (is_numeric($val) && (string)(int)$val === (string)$val) {
                                return null;
                            }
                            return $val;
                        })
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                    // If all values were numeric, use en/ar labels from same options if present
                    if (empty($examples) && !empty($field->options)) {
                        $first = $field->options[0];
                        if (is_array($first) && (isset($first['en']) || isset($first['ar']))) {
                            $examples = collect($field->options)
                                ->map(fn($o) => is_array($o) ? ($o['en'] ?? $o['ar'] ?? null) : $o)
                                ->filter()->unique()->values()->toArray();
                        }
                    }
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
     * Get display name (label) for Track for use in example Excel - avoids numeric IDs.
     */
    protected static function getTrackDisplayName(\App\Models\Track $track): string
    {
        $name = $track->name;
        if (is_array($name)) {
            return trim((string) ($name['en'] ?? $name['ar'] ?? $track->slug ?? ''));
        }
        return trim((string) ($name ?? $track->slug ?? ''));
    }

    /**
     * Get display name (label) for SubTrack for use in example Excel - avoids numeric IDs.
     */
    protected static function getSubTrackDisplayName(\App\Models\SubTrack $subtrack): string
    {
        $name = $subtrack->name;
        if (is_array($name)) {
            return trim((string) ($name['en'] ?? $name['ar'] ?? $subtrack->slug ?? ''));
        }
        return trim((string) ($name ?? $subtrack->slug ?? ''));
    }
}

