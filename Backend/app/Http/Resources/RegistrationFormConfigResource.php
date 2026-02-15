<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationFormConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'registration_type' => $this->registration_type,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'min_team_members' => $this->min_team_members,
            'max_team_members' => $this->max_team_members,
            'team_fields_enabled' => $this->team_fields_enabled,
            'labels' => [
                'register_as' => $this->label_register_as,
                'option_individual' => $this->option_register_individual,
                'option_team' => $this->option_register_team,
                'team_name' => $this->label_team_name,
                'team_logo' => $this->label_team_logo,
                'team_serial' => $this->label_team_serial,
                'help_team_serial' => $this->help_team_serial,
            ],
        ];
    }
}
