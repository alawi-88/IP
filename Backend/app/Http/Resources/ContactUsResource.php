<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ContactUsResource extends JsonResource
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
            'title' => $this->title,
            'message' => $this->message,
            'attachments' => collect($this->attachments)->map(fn($attachment) => Storage::url($attachment)),
            'status' => $this->status,
            'reply' => $this->reply,
            'replied_at' => $this->replied_at,
            'created_at' => $this->created_at,
        ];
    }
}
