<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStartupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
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
            'name' => ['required', 'string', 'max:255', 'unique:startups,name,NULL,id,user_id,' . auth()->id() . ',deleted_at,NULL'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sector' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:255'],
            'founding_date' => ['nullable', 'date', 'before_or_equal:today'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => 'name']),
            'name.unique' => __('validation.unique', ['attribute' => 'startup name']),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'tagline.max' => __('validation.max.string', ['attribute' => 'tagline', 'max' => 500]),
            'description.max' => __('validation.max.string', ['attribute' => 'description', 'max' => 5000]),
            'logo_path.image' => __('validation.image', ['attribute' => 'logo']),
            'logo_path.mimes' => __('validation.mimes', ['attribute' => 'logo', 'values' => 'jpg, jpeg, png']),
            'logo_path.max' => __('validation.max.file', ['attribute' => 'logo', 'max' => '2MB']),
            'founding_date.date' => __('validation.date', ['attribute' => 'founding date']),
            'founding_date.before_or_equal' => __('validation.before_or_equal', ['attribute' => 'founding date', 'date' => 'today']),
            'team_size.integer' => __('validation.integer', ['attribute' => 'team size']),
            'team_size.min' => __('validation.min.numeric', ['attribute' => 'team size', 'min' => 1]),
        ];
    }
}
