<?php

namespace App\Filters\Mentors;

use Closure;

final readonly class Profession
{
    public function handle($mentors, Closure $next)
    {
        request()->validate([
            'profession' => 'nullable|string|max:255',
        ]);

        $keyword = request('profession');

        if (!empty($keyword)) {
            $mentors = $mentors->where(function($query) use ($keyword) {
                $query->whereRaw("JSON_EXTRACT(profession, '$.en') LIKE ?", ["%{$keyword}%"])
                      ->orWhereRaw("JSON_EXTRACT(profession, '$.ar') LIKE ?", ["%{$keyword}%"]);
            });
        }

        return $next($mentors);
    }
}

