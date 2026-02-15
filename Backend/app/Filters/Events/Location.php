<?php

namespace App\Filters\Events;

use App\Models\Event;
use Closure;
use Illuminate\Support\Arr;

final readonly class Location
{
    public function handle($events, Closure $next)
    {
        request()->validate([
            'location' => 'string|in:' . Arr::join(Event::LOCATIONS, ','),
        ]);

        $keyword = request('location');

        if (!empty($keyword)) {
            $events = $events->where('location', $keyword);
        }
        return $next($events);
    }
}
