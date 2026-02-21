<?php

namespace App\Traits\Program;

trait FilterByProgram
{
    public function scopeByProgram($query)
    {
        return $query->where('program_id', currentProgramId());
    }
}
