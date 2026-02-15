<?php

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class SessionRequest extends FormRequest
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
        $rules = [
            'participant_id' => ['required', 'exists:participants,id'],
            'competition_id' => ['required', 'exists:competitions,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['integer', 'min:15', 'max:480'],
        ];

        // For updates, make some fields optional and exclude duration_minutes
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['participant_id'] = ['sometimes', 'exists:participants,id'];
            $rules['competition_id'] = ['sometimes', 'exists:competitions,id'];
            $rules['title'] = ['sometimes', 'string', 'max:255'];
            $rules['scheduled_at'] = ['sometimes', 'date', 'after:now'];

            // Remove duration_minutes from update rules (not allowed to change)
            unset($rules['duration_minutes']);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'participant_id.required' => __('sessions.participant_required'),
            'participant_id.exists' => __('sessions.participant_not_found'),
            'competition_id.required' => __('sessions.competition_required'),
            'competition_id.exists' => __('sessions.competition_not_found'),
            'title.max' => __('sessions.title_too_long'),
            'description.max' => __('sessions.description_too_long'),
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
        // Set default duration only for new sessions (not updates)
        if (!$this->isMethod('patch') && !$this->isMethod('put')) {
            if (!$this->has('duration_minutes')) {
                $this->merge(['duration_minutes' => 30]);
            } elseif ($this->input('duration_minutes') == 60) {
                // Override 60 to 30 if explicitly sent (temporary fix until frontend is updated)
                $this->merge(['duration_minutes' => 30]);
            }
        }

        // Convert scheduled_at to Carbon instance for validation
        // Ensure we preserve the exact time including minutes and seconds
        if ($this->has('scheduled_at')) {
            try {
                $scheduledAtInput = $this->input('scheduled_at');
                // Parse the datetime, ensuring we preserve the exact time
                $scheduledAt = Carbon::parse($scheduledAtInput);
                // Ensure we maintain the exact time by setting it explicitly
                $this->merge(['scheduled_at' => $scheduledAt]);
            } catch (\Exception $e) {
                // Let validation handle the error
            }
        }
    }
}
