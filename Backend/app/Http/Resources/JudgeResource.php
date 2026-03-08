<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JudgeResource extends JsonResource
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
            'program' => new ProgramResource($this->program),
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'experience_field' => $this->experience_field,
            'created_at' => $this->created_at,
        ];
    }
}
