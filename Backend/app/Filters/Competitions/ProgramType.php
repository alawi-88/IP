<?php

namespace App\Filters\Competitions;

use Closure;
use Illuminate\Support\Str;

final readonly class ProgramType
{
    public function handle($competitions, \Closure $next)
    {
        $programType = request('program_type');
        
        // Only apply filter if program_type parameter is provided and not empty
        if ($programType !== null && $programType !== '') {
            $competitions = $competitions->programType($programType);
        }
        
        return $next($competitions);
    }
}