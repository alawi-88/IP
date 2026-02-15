<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WinnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale();
        return [
            'id' => $this->id,
            'rank' => $this->rank,
            'rank_formatted' => $this->formatRank($this->rank),
            'name' => $this->name[$lang],
            'subtitle' => $this->subtitle[$lang],
            'image' => $this->image ? Storage::url($this->image) : null,
            'track' => $this->track ? [
                'id' => $this->track->id,
                'name' => $this->track->name,
                'winners_count' => $this->track->winners()->count(),
            ] : null,
        ];
    }

    protected function formatRank(int $rank): string
    {
        return match ($rank) {
            1 => '1st Place',
            2 => '2nd Place',
            3 => '3rd Place',
            default => $rank . 'th Place',
        };
    }
}
