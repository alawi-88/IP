<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiGenerateRequest extends FormRequest
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
        // No preparation needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'field_key' => ['required', 'string', 'max:255'],
            'prompt' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'field_key.required' => __('validation.required', ['attribute' => 'field key']),
            'field_key.string' => __('validation.string', ['attribute' => 'field key']),
            'field_key.max' => __('validation.max.string', ['attribute' => 'field key', 'max' => 255]),
            'prompt.required' => __('validation.required', ['attribute' => 'prompt']),
            'prompt.string' => __('validation.string', ['attribute' => 'prompt']),
            'prompt.max' => __('validation.max.string', ['attribute' => 'prompt', 'max' => 2000]),
        ];
    }
}
