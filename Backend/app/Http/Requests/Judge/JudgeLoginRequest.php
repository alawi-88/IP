<?php

namespace App\Http\Requests\Judge;

use Illuminate\Foundation\Http\FormRequest;

class JudgeLoginRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

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
        $rules = [
            'email' => 'required|email|exists:judges,email',
            'password' => 'required',
            'otp' => 'nullable|numeric|digits:6',
        ];

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => __('auth.invalid_email'),
            'email.email' => __('validation.email.format')
        ];
    }
}
