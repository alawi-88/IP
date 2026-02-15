<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserCompetition extends Pivot
{
    protected $table = 'user_competitions';

    protected $fillable = [
        'user_id',
        'competition_id',
    ];

    public function competitions(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
