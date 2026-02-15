<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnhanceFormRequest extends FormRequest
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
        return [
            'formId' => ['required', 'string'],
            'fields' => ['required', 'array'],
            'fields.*.fieldId' => ['required', 'string'],
            'fields.*.slug' => ['required', 'string'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.value' => ['nullable', 'string'],
            'fields.*.instructions' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'formId.required' => __('validation.required', ['attribute' => 'formId']),
            'fields.required' => __('validation.required', ['attribute' => 'fields']),
            'fields.array' => __('validation.array', ['attribute' => 'fields']),
        ];
    }
}


