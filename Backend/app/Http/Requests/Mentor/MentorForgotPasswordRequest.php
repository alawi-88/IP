<?php

namespace App\Http\Requests\Mentor;

use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class MentorForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $email = urldecode($this->query('email') ?? $this->input('email'));
            $email = str_replace(' ', '+', $email); // restore any '+' turned into spaces
            $this->merge(['email' => $email]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'g-recaptcha-response' => ['bail', 'required', new ReCaptcha()],
            'email' => ['required', 'email', 'exists:mentors,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => __('validation.required'),
            'email' => __('validation.email.format'),
            'exists' => __('validation.email.exists'),
        ];
    }
}
