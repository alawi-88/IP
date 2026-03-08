<?php

namespace App\Http\Requests;

use App\Models\ProgramApplication;
use App\Models\TeamFormConfig;
use App\Rules\QualifiedMember;
use Illuminate\Foundation\Http\FormRequest;
use \App\Models\Team;

class TeamRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->applications()->where('id', $this->application_id)->exists();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('serial_numbers') && is_string($this->input('serial_numbers'))) {
            $this->merge([
                'serial_numbers' => array_filter(array_map('trim', explode(',', $this->input('serial_numbers'))))
            ]);
        }
    }



    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $applicationId = $this->input('application_id');
            if (!$applicationId) return;

            $application = ProgramApplication::find($applicationId);

            if (!$application) return;

            // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
            // RegistrationFormConfig is the source of truth for team size limits
            // Note: scopeActive() already checks is_archived, so we don't need notArchived()
            $registrationConfig = \App\Models\RegistrationFormConfig::where('program_id', $application->program_id)
                ->active()
                ->first();
            
            // If RegistrationFormConfig exists, use it (it's the source of truth)
            if ($registrationConfig) {
                // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
                $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
            } else {
                // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
                $teamConfig = TeamFormConfig::where('program_id', $application->program_id)
                    ->active()
                    ->notArchived()
                    ->first();
                
                if ($teamConfig) {
                    // Use TeamFormConfig values - don't use ?? operator if value is explicitly null
                    $minTeamMembers = $teamConfig->min_team_members !== null ? $teamConfig->min_team_members : 2;
                    $maxTeamMembers = $teamConfig->max_team_members !== null ? $teamConfig->max_team_members : config('team.max_members', 6);
                } else {
                    // Use defaults if neither exists
                    $minTeamMembers = 2;
                    $maxTeamMembers = config('team.max_members', 6);
                }
            }

            $team = Team::where('application_id', $applicationId)->first();

            $serialNumbers = $this->input('serial_numbers', []);
            if (is_string($serialNumbers)) {
                $serialNumbers = array_filter(array_map('trim', explode(',', $serialNumbers)));
            } elseif (!is_array($serialNumbers)) {
                $serialNumbers = [];
            }

            // Remove duplicates
            $serialNumbers = array_unique($serialNumbers);

            $existingMembersCount = 0;
            $existingMemberParticipantIds = [];

            if ($team) {
                $existingMemberParticipantIds = $team->members()
                    ->where('participant_id', '!=', auth()->id()) // Exclude current user who will be leader
                    ->pluck('participant_id')
                    ->toArray();
                $existingMembersCount = count($existingMemberParticipantIds);
            }

            // Get participant IDs for the serial numbers being added
            $participantIds = \App\Models\Participant::whereIn('serial_number', $serialNumbers)
                ->pluck('id')
                ->toArray();

            // Filter out participants that already exist in the team
            $newParticipantIds = array_diff($participantIds, $existingMemberParticipantIds);
            // Also filter out the current user if they're in the serial numbers
            $newParticipantIds = array_diff($newParticipantIds, [auth()->id()]);
            $newMembersCount = count($newParticipantIds);
            
            // Calculate total: existing members (excluding leader) + new members + leader (always counted as 1)
            // The leader is ALWAYS counted as 1 member, regardless of whether they're already in the team
            $totalMembers = $existingMembersCount + $newMembersCount + 1; // Always add 1 for leader

            // Validate MINIMUM team size
            if ($totalMembers < $minTeamMembers) {
                $validator->errors()->add(
                    'serial_numbers',
                    __('program_application.The total number of team members must be at least :min.', ['min' => $minTeamMembers]) 
                        ?: "The total number of team members must be at least {$minTeamMembers}."
                );
            }

            // Validate MAXIMUM team size
            if ($totalMembers > $maxTeamMembers) {
                $validator->errors()->add(
                    'serial_numbers',
                    __('program_application.The total number of team members must not exceed :max.', ['max' => $maxTeamMembers])
                );
            }


            if (!$teamConfig || $teamConfig->allow_track_selection) return;
            $formSubmissions = $application->form_submissions->toArray();
            $submittedTrack = $formSubmissions['track'] ?? null;

            $trackIdFromRequest = $this->input('track_id');
            if ($trackIdFromRequest && $submittedTrack && (string)$trackIdFromRequest !== (string)$submittedTrack) {
                $validator->errors()->add('track_id', __('team_member.track_mismatch'));
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'application_id' => ['required', 'exists:program_applications,id'],
            'name' => ['required_if:has_team,1', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,png', 'max:1024'],
            'strength' => ['required_if:has_team,1', 'max:500'],
            'serial_numbers' => ['required_if:has_team,1', 'array', 'max:6'],
            'serial_numbers.*' => ['distinct', 'string', new QualifiedMember(), 'max:8'],
            'track_id' => ['required_if:has_idea,1', 'exists:tracks,id'],
            'sub_track_id' => ['sometimes','exists:sub_tracks,id'],
            'idea_description' => ['required_if:has_idea,1', 'max:300'],
            'previous_participation' => ['boolean'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:50'],
            'contact_email' => ['email', 'max:255'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Step 1 Errors
            'logo.image' => __('program_application.The team logo must be a valid image.'),
            'logo.max' => __('program_application.Image size must not exceed 1MB.'),
            'strength.required_if' => __('program_application.Please describe your team\'s strengths.'),
            'strength.max' => __('program_application.The maximum message limit is 500 characters.'),
            'serial_numbers.min' => __('program_application.At least one member must be added to the team.'),
            'serial_numbers.required_if' => __('program_application.At least one member must be added to the team.'),
            'serial_numbers.max' => __('program_application.The maximum number of team members is 6.'),

            // Step 2 Errors
            'track_id.exists' => __('program_application.Please select a valid path.'),
            'idea_challenge_id.exists' => __('program_application.Please select a valid challenge.'),
            'idea_description.max' => __('program_application.The maximum description limit is 300 characters.'),

            // Step 3 Errors
            'previous_participation.required' => __('program_application.Please specify if you or your team members have participated before.'),
        ];
    }
}
