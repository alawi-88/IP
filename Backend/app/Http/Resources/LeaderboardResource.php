<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $value = $this->resource['total_score'] ?? 0;
        return [
            'rank' => $this->resource['rank'] ?? null,
            'name' => $this->resource['name'] ?? '-',
            'is_team' => $this->resource['is_team'] ?? false,
            'registration_score' => $this->resource['registration_score'] ?? 0,
            'stage_scores' => collect($this->resource['stage_scores'] ?? [])->map(function ($stageScore) {
                return [
                    'stage_id' => $stageScore['stage_id'] ?? null,
                    'stage_title' => $stageScore['stage_title'] ?? null,
                    'stage_slug' => $stageScore['stage_slug'] ?? null,
                    'score' => floor($stageScore['score']) == $stageScore['score']
                            ? (int) $stageScore['score']
                            : number_format((float) $stageScore['score'], 2, '.', ''),
                ];
            })->values()->all(),
            'total_score' => floor($value) == $value
                            ? (int) $value
                            : number_format((float) $value, 2, '.', ''),
        ];
    }
}
