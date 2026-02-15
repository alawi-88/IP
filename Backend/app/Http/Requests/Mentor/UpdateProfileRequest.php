<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mentor = auth('mentors')->user() ?? auth()->user();
    
        return [
            // Accept name as array with ar/en keys OR as separate fields OR as single value
            'name' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        // Validate array format
                        if (isset($value['ar']) && !is_string($value['ar'])) {
                            $fail('The name.ar must be a string.');
                        }
                        if (isset($value['en']) && !is_string($value['en'])) {
                            $fail('The name.en must be a string.');
                        }
                    } elseif (is_string($value) && !preg_match('/^[\p{Arabic}A-Za-z\s]+$/u', $value)) {
                        $fail('The name format is invalid.');
                    }
                },
            ],
            'name.ar' => ['nullable', 'string', 'min:2'],
            'name.en' => ['nullable', 'string', 'min:2'],
            'name_ar' => ['nullable', 'string', 'min:2'],
            'name_en' => ['nullable', 'string', 'min:2'],
            'email' => [
                'required',
                'email',
                Rule::unique('mentors')->ignore($mentor->id ?? null),
            ],
            'phone' => [
                'required',
                'numeric',
                Rule::unique('mentors')->ignore($mentor->id ?? null),
                'digits_between:8,15',
            ],
            // Accept profession as array with ar/en keys OR as separate fields OR as single value
            'profession' => ['nullable'],
            'profession.ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'profession.en' => ['nullable', 'string', 'min:2', 'max:255'],
            'profession_ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'profession_en' => ['nullable', 'string', 'min:2', 'max:255'],
            // Accept experience as array with ar/en keys OR as separate fields OR as single value
            'experience' => ['nullable'],
            'experience.ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'experience.en' => ['nullable', 'string', 'min:2', 'max:255'],
            'experience_ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'experience_en' => ['nullable', 'string', 'min:2', 'max:255'],
            // Accept brief as array with ar/en keys OR as separate fields OR as single value
            'brief' => ['nullable'],
            'brief.ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'brief.en' => ['nullable', 'string', 'min:2', 'max:255'],
            'brief_ar' => ['nullable', 'string', 'min:2', 'max:255'],
            'brief_en' => ['nullable', 'string', 'min:2', 'max:255'],
            'linkedin' => [
                'nullable',
                'url',
                'max:255',
            ],
            'facebook' => [
                'nullable',
                'url',
                'max:255',
            ],
            'instagram' => [
                'nullable',
                'url',
                'max:255',
            ],
            'image' => [
                'nullable',
            ],
        ];
        
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
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
            'brief.required' => __('validation.required'),
            'brief.regex' => __('validation.experience_field.regex'),
            'brief.min' => __('validation.experience_field.min'),
            'image.required' => __('validation.required'),
            'image.image' => __('validation.image.image'),
            'image.mimes' => __('validation.image.mimes'),
            'image.max' => __('validation.image.max'),
            'linkedin.url' => __('validation.linkedin.url'),
            'linkedin.max' => __('validation.max'),
            'facebook.url' => __('validation.facebook.url'),
            'facebook.max' => __('validation.max'),
            'instagram.url' => __('validation.instagram.url'),
            'instagram.max' => __('validation.max'),
        ];
    }
}

