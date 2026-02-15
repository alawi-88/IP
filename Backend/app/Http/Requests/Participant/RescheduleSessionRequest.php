<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class RescheduleSessionRequest extends FormRequest
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
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure scheduled_at is provided and valid
            if (!$this->has('scheduled_at') || empty($this->input('scheduled_at'))) {
                $validator->errors()->add('scheduled_at', __('sessions.please_select_valid_slot'));
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'scheduled_at.required' => __('sessions.scheduled_at_required'),
            'scheduled_at.date' => __('sessions.scheduled_at_invalid'),
            'scheduled_at.after' => __('sessions.scheduled_at_must_be_future'),
            'duration_minutes.integer' => __('sessions.duration_must_be_integer'),
            'duration_minutes.min' => __('sessions.duration_too_short'),
            'duration_minutes.max' => __('sessions.duration_too_long'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert scheduled_at to Carbon instance for validation
        // If no timezone is provided, assume Asia/Riyadh (Saudi Arabia timezone)
        if ($this->has('scheduled_at')) {
            try {
                $dateString = $this->input('scheduled_at');

                // Check if string contains timezone info
                $hasTimezone = preg_match('/[+-]\d{2}:?\d{2}$|[A-Z]{3,4}$/', $dateString) ||
                              preg_match('/T.*[+-]\d{2}:?\d{2}/', $dateString);

                if ($hasTimezone) {
                    $scheduledAt = Carbon::parse($dateString);
                } else {
                    // No timezone - assume Asia/Riyadh
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $dateString, 'Asia/Riyadh');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateString)) {
                        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $dateString, 'Asia/Riyadh');
                    } else {
                        $scheduledAt = Carbon::parse($dateString, 'Asia/Riyadh');
                    }
                }

                $this->merge(['scheduled_at' => $scheduledAt]);
            } catch (\Exception $e) {
                // Let validation handle the error
            }
        }
    }
}

