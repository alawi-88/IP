<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramJudge extends Model
{
    protected $table = 'program_judge';

    protected $fillable = [
        'program_id',
        'judge_id',
    ];


    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
