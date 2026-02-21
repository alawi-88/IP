<?php

namespace App\Filters\Programs;

use Closure;
use Illuminate\Support\Str;

final readonly class ProgramType
{
    public function handle($programs, \Closure $next)
    {
        $programType = request('program_type');
        
        // Only apply filter if program_type parameter is provided and not empty
        if ($programType !== null && $programType !== '') {
            $programs = $programs->programType($programType);
        }
        
        return $next($programs);
    }
}