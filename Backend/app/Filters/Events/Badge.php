<?php

namespace App\Filters\Events;

use App\Models\Event;
use Closure;
use Illuminate\Support\Arr;

final readonly class Badge
{
    public function handle($events, Closure $next)
    {
        request()->validate([
            'badge' => 'string|in:' . Arr::join(Event::BADGES, ','),
        ]);

        $keyword = request('badge');

        if (!empty($keyword)) {
            $events = $events->where('badge', $keyword);
        }
        return $next($events);
    }
}
