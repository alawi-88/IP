<?php

namespace App\Rules;

use App\Models\CompetitionApplication;
use App\Models\Participant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailExistsInCompetition implements ValidationRule
{
    public function __construct(protected ?string $competitionId)
    {

    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->competitionId === null) {
            return;
        }

        if (CompetitionApplication::where('competition_id', $this->competitionId)
            ->where('participant_id', Participant::where('email', $value)->first()?->id)
            ->exists()) {
            $fail("The email has already been used in this program: ". $value);
        }
    }
}
