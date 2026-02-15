<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends FormRequest
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
            'start_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            'end_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
        ];

        // If recurring, require day_of_week, otherwise require date
        if ($this->input('is_recurring')) {
            $rules['day_of_week'] = [
                'required',
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
            ];
            $rules['date'] = ['nullable'];
        } else {
            $rules['date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['day_of_week'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_time.required' => __('mentor_availability.Start time is required'),
            'start_time.date_format' => __('mentor_availability.Invalid time format'),
            'end_time.required' => __('mentor_availability.End time is required'),
            'end_time.date_format' => __('mentor_availability.Invalid time format'),
            'end_time.after' => __('mentor_availability.End time must be after start time'),
            'date.required' => __('mentor_availability.Date is required'),
            'date.date' => __('mentor_availability.Invalid date format'),
            'date.after_or_equal' => __('mentor_availability.Date must be today or later'),
            'day_of_week.required' => __('mentor_availability.Day of week is required'),
            'day_of_week.in' => __('mentor_availability.Invalid day of week'),
        ];
    }
}

