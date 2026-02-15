<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SatisfactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->satisfactions()->doesntExist();
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'You have already submitted your satisfaction form.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'overall_experience' => 'required|integer|between:1,5',
            'benefit_from_training' => 'required|integer|between:1,5',
            'support_and_guidance_mentors' => 'required|integer|between:1,5',
            'support_organizers' => 'required|integer|between:1,5',
            'location_surrounding_environment' => 'required|integer|between:1,5',
            'interested_attending_similar_programs' => 'required|boolean',
            'how_did_you_hear_about_filmathon' => 'required|string|in:email,sms,social_media,friend,other',
            'suggestions_comments' => 'nullable|string',
        ];
    }
}
