<?php

namespace App\Filters\Teams;

use Closure;

final readonly class SubTracks
{
    public function handle($teams, Closure $next)
    {
        request()->validate([
            'sub_track_id' => 'string|exists:sub_tracks,id',
        ]);

        $keyword = request('sub_track_id');

        if (!empty($keyword)) {
            $teams = $teams->where('sub_track_id', $keyword);
        }
        return $next($teams);
    }
}
