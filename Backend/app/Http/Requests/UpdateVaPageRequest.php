<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $startup = $this->route('startup');
        return auth()->id() === $startup->user_id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Content should be a valid JSON structure
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'array'],
            'completion_percentage' => ['sometimes', 'numeric', 'between:0,100'],
            'status' => ['sometimes', 'in:draft,in_progress,completed'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'content.required' => __('validation.required', ['attribute' => 'content']),
            'content.array' => __('validation.array', ['attribute' => 'content']),
            'completion_percentage.numeric' => __('validation.numeric', ['attribute' => 'completion percentage']),
            'completion_percentage.between' => __('validation.between.numeric', ['attribute' => 'completion percentage', 'min' => 0, 'max' => 100]),
            'status.in' => __('validation.in', ['attribute' => 'status']),
        ];
    }
}
