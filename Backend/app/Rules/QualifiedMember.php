<?php

namespace App\Rules;

use App\Models\CompetitionApplication;
use App\Models\Participant;
use App\Models\TeamMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\TeamFormConfig;

readonly class QualifiedMember implements ValidationRule
{
    public function __construct(private ?bool $hasTeam = true)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->hasTeam) return;

        // if value does not have 8 digits
        if (strlen($value) !== 8) {
            $fail(__('competition_application.The member serial number must be 6 digits.', ['participant' => $value]));
            return;
        }

        $participant = Participant::where('serial_number', $value)->first();

        // if the member is not registered
        if (! $participant) {
            $fail(__('competition_application.The member must be registered on the platform to be added to the team.', ['participant' => $value]));
            return;
        }

        // if not have an application
        if (CompetitionApplication::where('participant_id', $participant->id)
            ->where('competition_id', getCompetitionId(request('application_id')))
            ->doesntExist()) {
            $fail(__('competition_application.The member is not registered in the program', ['participant' => $participant->serial_number]));
            return;
        }

        // if the member is the same as the authenticated user
        if ($participant && $participant->id === request()->user()->id) {
            $fail(__('competition_application.You cannot add yourself to the team', ['participant' => $participant->serial_number]));
            return;
        }

        $teamConfig = TeamFormConfig::where('competition_id',getCompetitionId(request('application_id')))
            ->notArchived() // Only use non-archived Team Form Configurations
            ->first();

        if ($teamConfig && $teamConfig->require_same_track) {

        $memberApplication = CompetitionApplication::where('participant_id', $participant->id)
            ->where('competition_id', getCompetitionId(request('application_id')))
            ->first();

        $teamLeadApplication = CompetitionApplication::where('participant_id', request()->user()->id)
            ->where('competition_id', getCompetitionId(request('application_id')))
            ->first();

        $memberTrack = data_get($memberApplication->form_submissions, 'track');
        $leadTrack = data_get($teamLeadApplication->form_submissions, 'track');


        if( $memberTrack != $leadTrack){
            $fail(__('competition_application.Team mambers must have the same track', ['participant' => $participant->serial_number]));
            return;
        }
        }

        // if the member is not approved
        if (CompetitionApplication::where('participant_id', $participant->id)
            ->where('competition_id', getCompetitionId(request('application_id')))
            ->where('status', 'approved')->doesntExist()) {
            $fail(__('competition_application.The member is not approved in the program', ['participant' => $participant->serial_number]));
            return;
        }

        // if the member is registered in another team (excluding archived teams)
        if ($participant && TeamMember::where('participant_id', $participant->id)
                ->whereHas('team', fn ($query) => $query->where('is_archived', false)
                    ->whereHas('application', fn ($query) => $query->where('competition_id', getCompetitionId(request('application_id')))))
                ->exists()) {
            $fail(__('competition_application.The member registered in the another team', ['participant' => $participant->serial_number]));
            return;
        }

        // Get team configuration - prioritize RegistrationFormConfig over TeamFormConfig
        // RegistrationFormConfig is the source of truth for team size limits
        // Note: scopeActive() already checks is_archived, so we don't need notArchived()
        $registrationConfig = \App\Models\RegistrationFormConfig::where('competition_id', getCompetitionId(request('application_id')))
            ->active()
            ->first();
        
        // If RegistrationFormConfig exists, use it (it's the source of truth)
        if ($registrationConfig) {
            // Use RegistrationFormConfig values - don't use ?? operator if value is explicitly null
            $minTeamMembers = $registrationConfig->min_team_members !== null ? $registrationConfig->min_team_members : 2;
            $maxTeamMembers = $registrationConfig->max_team_members !== null ? $registrationConfig->max_team_members : config('team.max_members', 6);
        } else {
            // Fallback to TeamFormConfig if RegistrationFormConfig doesn't exist
            $teamConfig = TeamFormConfig::where('competition_id', getCompetitionId(request('application_id')))
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

        $user = auth()->user();
        $team = $user?->team;
        
        // Get existing member participant IDs (excluding the current user who will be leader)
        $existingMemberParticipantIds = [];
        if ($team) {
            $existingMemberParticipantIds = $team->members()
                ->where('participant_id', '!=', $user->id)
                ->pluck('participant_id')
                ->toArray();
        }
        
        $serialNumbers = request()->input('serial_numbers');
        if (is_string($serialNumbers)) {
            $serialNumbers = array_filter(array_map('trim', explode(',', $serialNumbers)));
        } elseif (!is_array($serialNumbers)) {
            $serialNumbers = [];
        }
        
        // Remove duplicates
        $serialNumbers = array_unique($serialNumbers);
        
        // Get participant IDs for the serial numbers being added
        $participantIds = Participant::whereIn('serial_number', $serialNumbers)
            ->pluck('id')
            ->toArray();
        
        // Filter out participants that already exist in the team
        $newParticipantIds = array_diff($participantIds, $existingMemberParticipantIds);
        // Also filter out the current user if they're in the serial numbers
        $newParticipantIds = array_diff($newParticipantIds, [$user->id]);
        $newMembersCount = count($newParticipantIds);
        
        $existingMembersCount = count($existingMemberParticipantIds);
        
        // Calculate total: existing members (excluding leader) + new members + leader (always counted as 1)
        // The leader is ALWAYS counted as 1 member, regardless of whether they're already in the team
        $totalMembers = $existingMembersCount + $newMembersCount + 1; // Always add 1 for leader

        // Validate MINIMUM team size
        if ($totalMembers < $minTeamMembers) {
            $fail(__('competition_application.The total number of team members must be at least :min.', ['min' => $minTeamMembers])
                ?: "The total number of team members must be at least {$minTeamMembers}.");
            return;
        }

        // Validate MAXIMUM team size
        if ($totalMembers > $maxTeamMembers) {
            $fail(__('competition_application.The total number of team members must not exceed :max.', ['max' => $maxTeamMembers]));
            return;
        }
    }
}
