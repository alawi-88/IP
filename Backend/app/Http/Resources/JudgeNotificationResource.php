<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JudgeNotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    protected function getTitle(): ?string
    {
        if (isset($this->data['message'])) {
            return $this->data['message'] === 'reset_password' 
                ? __('passwords.reset_password') 
                : __('program_application.' . $this->data['message']);
        }

        return $this->data['title'] ?? null;
    }

    protected function getBody(): ?string
    {
        if (isset($this->data['message'])) {
            return null;
        }

        return $this->data['body'] ?? null;
    }
}
