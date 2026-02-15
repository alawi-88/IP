<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionJudge extends Model
{
    protected $table = 'competition_judge';

    protected $fillable = [
        'competition_id',
        'judge_id',
    ];


    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
