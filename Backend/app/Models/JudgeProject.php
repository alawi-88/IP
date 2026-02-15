<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JudgeProject extends Model
{
    protected $fillable = [
        'judge_id',
        'project_id',
        'evaluation_score',
        'disclaimer_accepted',
        'disclaimer_accepted_at',
        'final_comment'
    ];

    protected $casts = [
        'disclaimer_accepted' => 'boolean',
        'disclaimer_accepted_at' => 'datetime'
    ];

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ProjectEvaluation::class);
    }

    public function formEvaluationScore()
    {
        return $this->hasOne(FormEvaluationScore::class);
    }

}
