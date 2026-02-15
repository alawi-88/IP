<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'name' => $this->name,
            'email' => $this->email,
            'recovery_email' => $this->recovery_email,
            'email_verified_at' => $this->email_verified_at,
            'phone' => $this->phone,
            'gender' => __('participant.'. $this->gender),
            'date_of_birth' => $this->date_of_birth,
            'nationality' => $this->getNationalityName(),
            'country' => $this->country?->name,
            'residence_city' => $this->residenceCity?->name,
            'educational_background' => __('participant.'.$this->educational_background),
            'current_role' => __('participant.'.$this->current_role),
            'place_of_work_study' => $this->place_of_work_study,
            'years_of_experience' => __('participant.'.$this->years_of_experience),
            'experience_or_skills' => $this->experience_or_skills,
            'key_achievements' => $this->key_achievements,
            'last_login_at' => $this->last_login_at,
            'login_by' => $this->login_by,
            'nafath_data' => $this->nafath_data,
            'created_at' => $this->created_at,
            'is_active' => $this->is_active,
        ];
    }

    /**
     * Get nationality name with Nafath support
     */
    private function getNationalityName(): ?string
    {
        // If nationality relationship exists, return it
        if ($this->nationality) {
            return $this->nationality->name;
        }
        
        // If logged in via Nafath, try to get nationality from nafath_data
        if ($this->login_by === 'nafath' && $this->nafath_data) {
            $nationalityCode = $this->nafath_data['NationalityCode'] ?? null;
            if ($nationalityCode) {
                return \App\Models\NafathNationalityCode::getNationalityNameFromCode($nationalityCode) ?? 'N/A';
            }
        }
        
        return 'N/A';
    }
}
