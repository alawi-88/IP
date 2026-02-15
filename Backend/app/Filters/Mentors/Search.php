<?php

namespace App\Filters\Mentors;

use Closure;

final readonly class Search
{
    public function handle($mentors, Closure $next)
    {
        request()->validate([
            'search' => 'nullable|string|min:2|max:255',
        ]);

        $keyword = request('search');

        if (!empty($keyword)) {
            $mentors = $mentors->where(function($query) use ($keyword) {
                $lowerKeyword = mb_strtolower($keyword);
                $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(profession, '$.en'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(profession, '$.ar'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(experience, '$.en'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(experience, '$.ar'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(brief, '$.en'))) LIKE ?", ["%{$lowerKeyword}%"])
                      ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(brief, '$.ar'))) LIKE ?", ["%{$lowerKeyword}%"]);
            });
        }

        return $next($mentors);
    }
}

