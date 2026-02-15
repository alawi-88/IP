<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class ReCaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment(['staging', 'local'])) {
            return;
        }

        $secret = (string) config('recaptchav3.secret', '');
        $origin = rtrim((string) config('recaptchav3.origin', 'https://www.google.com/recaptcha'), '/');

        if ($secret === '') {
            // Fail closed in production-like environments if reCAPTCHA is not configured.
            $fail(__('validation.recaptcha.failed'));
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post("{$origin}/api/siteverify", [
                    'secret' => $secret,
                    'response' => $value,
                ]);
        } catch (\Throwable $e) {
            $fail(__('validation.recaptcha.failed'));
            return;
        }

        if (!($response->json()["success"] ?? false)) {

            $fail(__('validation.recaptcha.failed'));
        }
    }
}
