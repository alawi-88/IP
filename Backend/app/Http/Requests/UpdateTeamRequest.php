<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
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
        return [
            'application_id' => ['required', 'exists:program_applications,id'],
            'name' => ['required_if:has_team,1', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,png', 'max:1024'],
            'strength' => ['required_if:has_team,1', 'max:500'],
            'track_id' => ['required_if:has_idea,1', 'exists:tracks,id'],
            'sub_track_id' => ['sometimes', 'exists:sub_tracks,id'],
            'idea_description' => ['required_if:has_idea,1', 'max:300'],
            'previous_participation' => ['boolean'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:50'],
            'contact_email' => ['email', 'max:255'],
        ];
    }
}
