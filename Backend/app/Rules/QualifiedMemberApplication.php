<?php

namespace App\Rules;

use App\Models\CompetitionApplication;
use App\Models\Participant;
use App\Models\TeamMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

readonly class QualifiedMemberApplication implements ValidationRule
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
            $fail(__('competition_application.The member serial number must be 6 digits.'));
            return;
        }

        $participant = Participant::where('serial_number', $value)->first();

        // if the member is the same as the authenticated user
        if ($participant && $participant->id === request()->user()->id) {
            $fail(__('competition_application.You cannot add yourself to the team', ['participant' => $participant->serial_number]));
            return;
        }

        // if the member is not registered
        if (! $participant) {
            $fail(__('competition_application.The member must be registered on the platform to be added to the team.', ['participant' => $value]));
            return;
        }

        // if the member is not approved
        if (CompetitionApplication::where('participant_id', $participant->id)
            ->where('competition_id', request('application_id') ? getCompetitionId(request('application_id')) : request('competition_id'))
            ->exists()) {
            $fail(__('competition_application.The member registered in the program', ['participant' => $participant->serial_number]));
            return;
        }

        // if the member is registered in another team (excluding archived teams)
        if ($participant && TeamMember::where('participant_id', $participant->id)
                ->whereHas('team', fn ($query) => $query->where('is_archived', false)
                    ->whereHas('application', fn ($query) => $query->where('competition_id', request('competition_id'))))
                ->exists()) {

            $fail(__('competition_application.The member registered in the another team', ['participant' => $participant->serial_number]));
            return;
        }
    }
}
