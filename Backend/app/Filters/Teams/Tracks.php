<?php

namespace App\Filters\Teams;

use Closure;

final readonly class Tracks
{
    public function handle($teams, Closure $next)
    {
        request()->validate([
            'track_id' => 'string|exists:tracks,id',
        ]);

        $keyword = request('track_id');

        if (!empty($keyword)) {
            $teams = $teams->where('track_id', $keyword);
        }
        return $next($teams);
    }
}
