<?php

namespace App\Traits\Competition;

trait FilterByCompetition
{
    public function scopeByCompetition($query)
    {
        return $query->where('competition_id', currentCompetitionId());
    }
}
