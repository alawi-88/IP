<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserProgram extends Pivot
{
    protected $table = 'user_programs';

    protected $fillable = [
        'user_id',
        'program_id',
    ];

    public function programs(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
