<?php

namespace App\Http\Requests\Mentor;

use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
        $rules = [
            'name' => [
                'required',
                'regex:/^[\p{Arabic}A-Za-z\s]+$/u',
                'min:2',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('mentors', 'email'),
            ],
            'phone' => [
                'required',
                'numeric',
                Rule::unique('mentors', 'phone'),
                'digits_between:8,15',
            ],
            'profession' => [
                'required',
                // Accepts Arabic, letters, numbers, spaces, and "+", "-", "/", "."
                //'regex:/^[\p{Arabic}A-Za-z0-9\s\+\-\/\.]+$/u',
                'min:2',
                'max:255',
            ],
            'experience' => [
                'required',
                // Accepts Arabic, letters, numbers, spaces, and "+", "-", "/", "."
                //'regex:/^[\p{Arabic}A-Za-z0-9\s\+\-\/\.]+$/u',
                'min:2',
                'max:255',
            ],
            'password' => [
                'required',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/\d/',
                'regex:/[!@#$%^&*_\-]/',
            ],
        ];

            $rules['g-recaptcha-response'] = ['bail', 'required', new ReCaptcha()];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required'),
            'name.regex' => __('validation.name.regex'),
            'name.min' => __('validation.name.min'),
            'email.required' => __('validation.required'),
            'email.email' => __('validation.email.format'),
            'email.unique' => __('validation.email.unique'),
            'phone.required' => __('validation.required'),
            'phone.numeric' => __('validation.phone.numeric'),
            'phone.unique' => __('validation.phone.unique'),
            'phone.digits_between' => __('validation.phone.digits_between'),
            'profession.required' => __('validation.required'),
            'profession.regex' => __('validation.experience_field.regex'),
            'profession.min' => __('validation.experience_field.min'),
            'experience.required' => __('validation.required'),
            'experience.regex' => __('validation.experience_field.regex'),
            'experience.min' => __('validation.experience_field.min'),
            'password.required' => __('validation.required'),
            'password.confirmed' => __('validation.password.confirmed'),
            'password.min' => __('validation.password.min'),
            'password.regex' => __('validation.password.regex'),
            'g-recaptcha-response.required' => __('validation.recaptcha.required'),
            'g-recaptcha-response.recaptcha' => __('validation.recaptcha.recaptcha'),
        ];
    }
}
