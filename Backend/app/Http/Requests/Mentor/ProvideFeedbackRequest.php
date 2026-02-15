<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class ProvideFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comments' => ['required', 'string'],
            'strengths' => ['required', 'string'],
            'improvements' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => __('sessions.fields.overall_rating'),
            'comments' => __('sessions.fields.comments'),
            'strengths' => __('sessions.fields.strengths'),
            'improvements' => __('sessions.fields.areas_for_improvement'),
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => __('sessions.validation.rating_required'),
            'rating.between' => __('sessions.validation.rating_invalid'),
            'comments.required' => __('sessions.validation.comments_required'),
            'strengths.required' => __('sessions.validation.strengths_required'),
            'improvements.required' => __('sessions.validation.improvements_required'),
        ];
    }
}



