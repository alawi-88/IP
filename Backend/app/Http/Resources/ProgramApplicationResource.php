<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class ProgramApplicationResource extends JsonResource
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

            'has_comment' => (bool) $this->comments()
                ->where('user_id', '!=', null)
                ->whereNull('author_type')
                ->where('is_read', false)
                ->count(),

            'participant_id' => $this->participant_id,
            'program' => new ProgramResource($this->program, null, $this->id),
            'form_id'        => $this->form_id,
            'submit_type' => !empty($this->type) ? $this->type : 'submission',
            'form'           => new ProgramRegistrationFormResource(
                $this->form,
                ($this->form_submissions instanceof SchemalessAttributes)
                    ? $this->form_submissions->toArray()
                    : [],
                $this->type ?? 'submission'
            ),
            'team_metadata' => [
                'register_as' => $this->getRegisterAs(),
                'team_name' => $this->team_name ?? null,
                'team_logo' => $this->when($this->team_logo, Storage::url($this->team_logo)),
                'team_serial' => is_array($this->team_serial)
                    ? implode(',', $this->team_serial)
                    : $this->team_serial,
            ],
            'team' => $this->when($this->team, new TeamResource($this->team)),
            'created_at' => $this->created_at,
            'status' => $this->status,
        ];
    }

    /**
     * Get register_as from form_submissions or fallback to registered_as field
     */
    private function getRegisterAs()
    {
        $formSubmissions = $this->form_submissions instanceof SchemalessAttributes
            ? $this->form_submissions->toArray()
            : (array) $this->form_submissions;

        return $formSubmissions['register_as'] ?? $this->registered_as ?? 'individual';
    }
}
