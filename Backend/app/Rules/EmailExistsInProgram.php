<?php

namespace App\Rules;

use App\Models\ProgramApplication;
use App\Models\Participant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailExistsInProgram implements ValidationRule
{
    public function __construct(protected ?string $programId)
    {

    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->programId === null) {
            return;
        }

        if (ProgramApplication::where('program_id', $this->programId)
            ->where('participant_id', Participant::where('email', $value)->first()?->id)
            ->exists()) {
            $fail("The email has already been used in this program: ". $value);
        }
    }
}
