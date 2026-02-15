<?php

namespace App\Filament\Imports;

//use App\Models\Challenge;
use App\Models\CompetitionApplication;
use App\Models\Participant;
use App\Models\SubTrack;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Track;
use App\Notifications\CompetitionRegistration;
use App\Rules\EmailExistsInCompetition;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Arr;

class CompetitionApplicationImporter extends Importer
{
    protected static ?string $model = CompetitionApplication::class;

    public static function getColumns(): array
    {
        $tracks = Track::pluck('slug', 'slug')->keys()->toArray();
        //$challenges =  \App\Models\Challenge::pluck('slug', 'slug')->keys()->toArray();
        $challenges = [];
        $paths = [];
        //$paths = Path::pluck('slug', 'slug')->keys()->toArray();
        $booleanOptions = ['Yes', 'No'];

        return [
            ImportColumn::make('competition_id')
                ->requiredMapping()
                ->rules(['required', 'numeric', 'exists:competitions,id']),

            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->rules(['required', 'email', function ($attribute, $value, $fail) {
                    // Participant will be created automatically if not found
                    $normalizedEmail = strtolower(trim($value));
                    $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
                    if ($participant && CompetitionApplication::where('competition_id', $this->data['competition_id'])
                        ->where('participant_id', $participant->id)
                        ->exists()) {
                        $fail("The email has already been used in this program: " . $value);
                    }
                }]),

            ImportColumn::make('team_name')
                ->requiredMapping()
                ->rules(['max:255']),

            ImportColumn::make('team_strength')
                ->requiredMapping()
                ->rules(['required_with:team_name', 'max:500']),

            ImportColumn::make('path')
                ->label('Track')
                ->examples($paths)
                ->requiredMapping()
                ->rules(['required', 'exists:paths,slug']),

            ImportColumn::make('challenge')
                //->examples($challenges)
                ->examples([])
                ->requiredMapping()
                ->rules(['nullable', 'sometimes', 'exists:challenges,slug']),

            ImportColumn::make('idea_description')
                ->requiredMapping()
                ->rules(['required_with:team_name', 'max:300']),

            ImportColumn::make('participation_interest')
                ->requiredMapping()
                ->rules(['required', 'max:300']),

            ImportColumn::make('team_member_previous_participation')
                ->examples($booleanOptions)
                ->requiredMapping()
                ->rules(['required', 'in:Yes,No']),

            ImportColumn::make('members_emails')
                ->requiredMapping()
                ->array(', ')
                ->rules(['array', 'required_with:team_name', 'max:255', function ($attribute, $value, $fail) {
                    if (count($value) > 5) {
                        $fail("Total members should not be more than 6.");
                    }
                }])
                ->nestedRecursiveRules(['required', 'email', function ($attribute, $value, $fail) {
                    // check if the email is the same as the team leader email (case-insensitive)
                    $normalizedValue = strtolower(trim($value));
                    $normalizedLeaderEmail = strtolower(trim($this->data['email']));
                    if ($normalizedValue == $normalizedLeaderEmail) {
                        $fail("The email is the same as the team leader email: " . $value);
                    }

                    // Check if the email has already been used in another team
                    $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedValue])->first();
                    if ($participant && TeamMember::where('participant_id', $participant->id)
                        ->whereHas('team', fn($query) => $query->whereHas('application', fn($query) => $query->where('competition_id', $this->data['competition_id'])))
                        ->exists()) {
                        $fail("The participant is already in another team: " . $value);
                    }

//                    if (CompetitionApplication::where('competition_id', $this->data['competition_id'])
//                        ->where('participant_id', Participant::where('email', $value)->first()?->id)
//                        ->doesntExist()) {
//                        $fail("The email has not been registered for this competition: " . $value);
//                    }
                }])
        ];
    }

    public function beforeFill()
    {
        Arr::forget($this->data, [
            'email',
            'team_name',
            'team_strength',
            'path',
            'challenge',
            'idea_description',
            'team_member_previous_participation',
            'members_emails'
        ]);
    }


    public function resolveRecord(): ?CompetitionApplication
    {
        CompetitionApplication::flushEventListeners();

        // Normalize email to lowercase for case-insensitive lookup
        $normalizedEmail = strtolower(trim($this->data['email']));
        $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

        // If participant doesn't exist, create a new one with minimal required data
        if (!$participant) {
            // Extract name from data if available, otherwise use email prefix
            $name = $this->data['name'] ?? $this->data['participant_name'] ?? explode('@', $normalizedEmail)[0];

            // Extract phone from data if available, generate unique one if not
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
                'nationality_id' => $this->data['nationality_id'] ?? 1, // Default nationality (adjust as needed)
                'country_id' => $this->data['country_id'] ?? 1, // Default country (adjust as needed)
                'residence_city_id' => $this->data['residence_city_id'] ?? 1, // Default city (adjust as needed)
                'educational_background' => $this->data['educational_background'] ?? 'bachelor',
                'current_role' => $this->data['current_role'] ?? 'university_student',
                'years_of_experience' => $this->data['years_of_experience'] ?? 'no_experience',
                'password' => \Illuminate\Support\Str::random(16), // Generate random password
                'email_verified_at' => now(), // Auto-verify imported participants
                'is_active' => true,
            ]);
        }

        $application = new CompetitionApplication([
            'participant_id' => $participant?->id,
            'participation_interest' => $this->data['participation_interest'],
            'has_idea' => isset($this->data['track']),
            'registered_as_team' => isset($this->data['team_name']),
            'status' => 'approved',
            'has_team' => isset($this->data['team_name']),
        ]);

        $participant?->notify(new CompetitionRegistration($application));

        return $application;
    }

    public function afterCreate()
    {
        $this->formatDataArray();

        if ($this->data['team_name']) {
            $team = Team::create([
                'application_id' => $this->record->id,
                'name' => $this->data['team_name'],
                'strength' => $this->data['team_strength'],
                'track_id' => Track::where('slug', $this->data['track'])->first()?->id,
                'sub_track_id' => SubTrack::where('slug', $this->data['sub_track'] ?? null)->first()?->id,
                'idea_description' => $this->data['idea_description'],
                'previous_participation' => $this->data['team_member_previous_participation'] == 'Yes',
            ]);

            $team->members()->create([
                'participant_id' => $this->record->participant_id,
                'is_leader' => true,
            ]);

            $membersEmails = $this->data['members_emails'];
            $membersEmails = explode(', ', $membersEmails);

            collect($membersEmails)->each(function ($email) use ($team) {
                // Normalize email to lowercase for case-insensitive lookup
                $normalizedEmail = strtolower(trim($email));
                $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

                // If participant doesn't exist, create a new one
                if (!$participant) {
                    $name = explode('@', $normalizedEmail)[0];
                    // Generate a unique phone number
                    do {
                        $phone = '050' . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                    } while (Participant::where('phone', $phone)->exists());

                    $participant = Participant::create([
                        'name' => $name,
                        'email' => $normalizedEmail,
                        'phone' => $phone,
                        'gender' => 'male',
                        'date_of_birth' => now()->subYears(20)->format('Y-m-d'),
                        'nationality_id' => 1,
                        'country_id' => 1,
                        'residence_city_id' => 1,
                        'educational_background' => 'bachelor',
                        'current_role' => 'university_student',
                        'years_of_experience' => 'no_experience',
                        'password' => \Illuminate\Support\Str::random(16),
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]);
                }

                if ($participant) {
                    $application = CompetitionApplication::create([
                        'competition_id' => $this->data['competition_id'],
                        'participant_id' => $participant->id,
                        'participation_interest' => $this->data['participation_interest'],
                        'has_idea' => isset($this->data['track']),
                        'registered_as_team' => false,
                        'status' => 'approved',
                        'has_team' => true,
                    ]);

                    $participant->notify(new CompetitionRegistration($application));

                    if ($team) {
                        $team->members()->create([
                            'participant_id' => $participant->id,
                            'is_leader' => false,
                        ]);
                    }
                }
            });
        }
    }

    public function getValidationRules(): array
    {
        return [
            'competition_id' => ['required', 'numeric', 'exists:competitions,id'],
            'email' => ['required', 'email', new EmailExistsInCompetition($this->data['competition_id'])],
            'team_name' => ['max:255'],
            'team_strength' => ['required_with:team_name,Yes', 'max:500'],
            'path' => ['required', 'exists:paths,slug'],
            'challenge' => ['nullable', 'sometimes', 'exists:challenges,slug'],
            'idea_description' => ['required_with:team_name', 'max:300'],
            'participation_interest' => ['required', 'max:300'],
            'team_member_previous_participation' => ['required', 'in:Yes,No'],
            'members_emails' => ['array', 'required_with:team_name', 'max:255', function ($attribute, $value, $fail) {
                if (count($value) > 5) {
                    $fail("Total members should not be more than 6.");
                }
            }],
            'members_emails.*' => ['required', 'email', function ($attribute, $value, $fail) {
                // check if the email is the same as the team leader email (case-insensitive)
                $normalizedValue = strtolower(trim($value));
                $normalizedLeaderEmail = strtolower(trim($this->data['email']));
                if ($normalizedValue == $normalizedLeaderEmail) {
                    $fail("The email is the same as the team leader email: " . $value);
                }

                // Check if the email has already been used in another team
                $participant = Participant::whereRaw('LOWER(email) = ?', [$normalizedValue])->first();
                if ($participant && TeamMember::where('participant_id', $participant->id)
                    ->whereHas('team', fn($query) => $query->whereHas('application', fn($query) => $query->where('competition_id', $this->data['competition_id'])))
                    ->exists()) {
                    $fail("The participant is already in another team: " . $value);
                }

//                if (CompetitionApplication::where('competition_id', $this->data['competition_id'])
//                    ->where('participant_id', Participant::where('email', $value)->first()?->id)
//                    ->doesntExist()) {
//                    $fail("The email has not been registered for this program: " . $value);
//                }
            }]
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'competition_id.required' => 'Program Id is required.',
            'competition_id.exists' => 'Please add a valid program Id.',
            'email.required' => 'Participant email is required.',
            'email.email' => 'Participant email must be a valid email address.',
            'email.exists' => 'Participant email is not registered.',
            'team_name.required_with' => 'Team name is required.',
            'team_name.string' => 'Team name must be a string.',
            'team_name.max' => 'Team name may not be greater than 255 characters.',
            'team_strength.required_with' => 'Team strength is required.',
            'team_strength.max' => 'Team strength may not be greater than 500 characters.',
            'path.required' => 'Track is required.',
            'path.exists' => 'Please add a valid track.',
            'challenge.exists' => 'Please add a valid challenge.',
            'idea_description.max' => 'Idea description may not be greater than 300 characters.',
            'participation_interest.required' => 'Participation interest is required.',
            'participation_interest.max' => 'Participation interest may not be greater than 300 characters.',
            'team_member_previous_participation.required' => 'Team member previous participation is required.',
            'team_member_previous_participation.in' => 'Please add a valid team member previous participation.',
            'members_emails.*.exists' => 'Member email is not registered.',
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your program application import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }


    private function formatDataArray(): void
    {
        $this->data = collect($this->data)->mapWithKeys(function ($value, $key) {
            return [str_replace(' ', '_', strtolower($key)) => $value];
        })->toArray();
    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addMinute();
    }
}
