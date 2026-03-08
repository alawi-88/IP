<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class BookSessionRequest extends FormRequest
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
            'application_id' => ['required', 'exists:program_applications,id'],
            'mentor_id' => ['sometimes', 'exists:mentors,id'], // Optional when coming from route parameter
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'application_id.required' => __('sessions.application_id_required'),
            'application_id.exists' => __('sessions.application_not_found'),
            'mentor_id.required' => __('sessions.mentor_id_required'),
            'mentor_id.exists' => __('sessions.mentor_not_found'),
            'title.max' => __('sessions.title_too_long'),
            'description.max' => __('sessions.description_too_long'),
            'scheduled_at.required' => __('sessions.scheduled_at_required'),
            'scheduled_at.date' => __('sessions.scheduled_at_invalid'),
            'scheduled_at.after' => __('sessions.scheduled_at_must_be_future'),
            'duration_minutes.required' => __('sessions.duration_required'),
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
        // Set default duration if not provided, or override 60 to 30
        if (!$this->has('duration_minutes')) {
            $this->merge(['duration_minutes' => 30]);
        } elseif ($this->input('duration_minutes') == 60) {
            // Override 60 to 30 if explicitly sent (temporary fix until frontend is updated)
            $this->merge(['duration_minutes' => 30]);
        }

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

