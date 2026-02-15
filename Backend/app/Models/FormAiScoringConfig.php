<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasActivityLog;

class FormAiScoringConfig extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'form_id',
        'ai_prompt',
        'total_weight',
    ];

    protected $casts = [
        'total_weight' => 'integer',
    ];

    protected array $logFields = [
        'form_id',
        'ai_prompt',
        'total_weight',
    ];

    protected string $moduleName = 'Form AI Scoring Config';
    protected string $logName = 'form_ai_scoring_config';

    /**
     * Get the form that owns this config.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get all assessment criteria for this config.
     */
    public function assessmentCriteria(): HasMany
    {
        return $this->hasMany(FormAssessmentCriterion::class, 'form_id', 'form_id');
    }

    /**
     * Get only active assessment criteria.
     */
    public function activeAssessmentCriteria(): HasMany
    {
        return $this->assessmentCriteria()->where('status', 'active');
    }

    /**
     * Calculate allocated weight (sum of active criteria weights).
     */
    public function getAllocatedWeightAttribute(): int
    {
        return $this->activeAssessmentCriteria()->sum('weight');
    }

    /**
     * Calculate remaining weight.
     */
    public function getRemainingWeightAttribute(): int
    {
        return max(0, $this->total_weight - $this->allocated_weight);
    }

    /**
     * Check if configuration is complete (allocated weight equals total weight).
     */
    public function isComplete(): bool
    {
        return $this->allocated_weight === $this->total_weight;
    }
}

