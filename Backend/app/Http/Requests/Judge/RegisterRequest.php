<?php

namespace App\Http\Requests\Judge;

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
                Rule::unique('judges', 'email'),
            ],
            'phone_number' => [
                'required',
                'numeric',
                Rule::unique('judges', 'phone_number'),
                'digits_between:8,15',
            ],
            'experience_field' => [
                'required',
                'regex:/^[\p{Arabic}A-Za-z\s]+$/u',
                'min:2',
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
            'phone_number.required' => __('validation.required'),
            'phone_number.numeric' => __('validation.phone.numeric'),
            'phone_number.unique' => __('validation.phone.unique'),
            'phone_number.digits_between' => __('validation.phone.digits_between'),
            'experience_field.required' => __('validation.required'),
            'experience_field.regex' => __('validation.experience_field.regex'),
            'experience_field.min' => __('validation.experience_field.min'),
            'password.required' => __('validation.required'),
            'password.confirmed' => __('validation.password.confirmed'),

        ];
    }
}
