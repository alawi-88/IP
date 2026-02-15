<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'logo' => $this->logo ? Storage::url(ltrim(str_replace(Storage::url('/'), '', $this->logo), '/')) : null,
//            'strength' => $this->strength,
            'members' => TeamMemberResource::collection($this->members),
            'track' => new TrackResource($this->track),
            'sub_track' => new subTrackResource($this->subTrack),
            'idea_description' => $this->idea_description,
            'previous_participation' => $this->previous_participation,
            'skills' => $this->whenNotNull($this->skills),
            'contact_email' => $this->contact_email,
            'is_completed' => $this->is_completed,
            'is_published' => $this->is_published,
            'is_participant_leader' => $this->when($request->routeIs('my-team.show'), $this->isParticipantLeader()),
        ];
    }
}
