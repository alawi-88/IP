<?php

namespace App\Http\Requests;

use App\Models\CompetitionApplication;
use App\Models\Team;
use App\Models\TeamFormConfig;
use App\Rules\QualifiedMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateTeamMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'serial_numbers' => ['required', 'array'],
            'serial_numbers.*' => ['required', 'string', 'max:8', new QualifiedMember(), ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $team = $this->route('team');

            if (!$team) {
                return;
            }

            $application = $team->application;
            if (!$application) {
                return;
            }

            // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
            // RegistrationFormConfig is the source of truth for team size limits
            // Note: scopeActive() already checks is_archived, so we don't need notArchived()
            $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', $application->competition_id)
                ->active()
                ->first();
            
            // If RegistrationFormConfig exists, use it (it's the source of truth)
            if ($registrationConfig) {
                // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
                $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
                $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
            } else {
                // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
                $teamConfig = TeamFormConfig::where('competition_id', $application->competition_id)
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

            $serialNumbers = $this->input('serial_numbers', []);

            if (is_string($serialNumbers)) {
                $serialNumbers = array_filter(array_map('trim', explode(',', $serialNumbers)));
            } elseif (!is_array($serialNumbers)) {
                $serialNumbers = [];
            }

            // Remove duplicates
            $serialNumbers = array_unique($serialNumbers);

            // Get existing member participant IDs (excluding the current user who is the leader)
            $existingMemberParticipantIds = $team->members()
                ->where('participant_id', '!=', auth()->id())
                ->pluck('participant_id')
                ->toArray();

            // Get participant IDs for the serial numbers being added
            $participantIds = \App\Models\Participant::whereIn('serial_number', $serialNumbers)
                ->pluck('id')
                ->toArray();

            // Filter out participants that already exist in the team
            $newParticipantIds = array_diff($participantIds, $existingMemberParticipantIds);
            // Also filter out the current user if they're in the serial numbers
            $newParticipantIds = array_diff($newParticipantIds, [auth()->id()]);
            $newMembersCount = count($newParticipantIds);
            
            $existingMembersCount = count($existingMemberParticipantIds);
            
            // Calculate total: existing members (excluding leader) + new members + leader (always counted as 1)
            // The leader is ALWAYS counted as 1 member, regardless of whether they're already in the team
            $totalMembers = $existingMembersCount + $newMembersCount + 1; // Always add 1 for leader

            // Validate MINIMUM team size
            if ($totalMembers < $minTeamMembers) {
                $validator->errors()->add(
                    'serial_numbers',
                    __('competition_application.The total number of team members must be at least :min.', ['min' => $minTeamMembers])
                        ?: "The total number of team members must be at least {$minTeamMembers}."
                );
            }

            // Validate MAXIMUM team size
            if ($totalMembers > $maxTeamMembers) {
                $validator->errors()->add(
                    'serial_numbers',
                    __('competition_application.The total number of team members must not exceed :max.', ['max' => $maxTeamMembers])
                );
            }
        });
    }
}
