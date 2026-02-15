<?php

namespace App\Http\Requests\Mentor;

use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class MentorResetPasswordRequest extends FormRequest
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
            'g-recaptcha-response' => ['bail', 'required', new ReCaptcha()],
            'code' => ['required', 'string'],
            'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*_-]).{12,}$/', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => __('validation.required'),
            'password.regex' => __('validation.password.complexity'),
            'password.confirmed' => __('validation.password.confirmed'),
        ];
    }
}
