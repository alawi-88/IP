<?php

namespace App\Filters\Mentors;

use Closure;

final readonly class Path
{
    public function handle($events, Closure $next)
    {
        request()->validate([
            'path_id' => 'integer|exists:paths,id',
        ]);

        $keyword = request('path_id');

        if (!empty($keyword)) {
            $events = $events->where('track_id', $keyword);
        }
        return $next($events);
    }
}
