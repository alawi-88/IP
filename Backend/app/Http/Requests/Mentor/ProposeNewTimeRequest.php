<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ProposeNewTimeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'proposed_time' => ['required', 'date', 'after:now'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'proposed_time.required' => __('sessions.proposed_time_required'),
            'proposed_time.date' => __('sessions.invalid_time_format'),
            'proposed_time.after' => __('sessions.proposed_time_must_be_future'),
        ];
    }
}

