<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramTabResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tab' => $this->tab,
            'label_en' => $this->label_en,
            'label_ar' => $this->label_ar,
            'is_visible' => $this->is_visible,
        ];
    }
}
