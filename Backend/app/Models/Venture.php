<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Venture extends Model
{
    protected $fillable = [
        'title',
        'idea_prompt',
        'status',
        'viability_score',
        'viability_breakdown',
        'industry',
        'target_market',
        'business_model',
        'sections_total',
        'sections_completed',
        'sections_failed',
        'competition_id',
        'team_id',
        'created_by',
        'generation_started_at',
        'generation_completed_at',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'viability_breakdown' => 'array',
        'is_archived' => 'boolean',
        'generation_started_at' => 'datetime',
        'generation_completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the competition this venture belongs to.
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get the participant (user) who created this venture.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all tabs for this venture.
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(VentureTab::class);
    }

    /**
     * Get all competitors for this venture.
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(VentureCompetitor::class);
    }

    /**
     * Get all sections through tabs.
     */
    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(
            VentureSection::class,
            VentureTab::class,
            'venture_id',
            'venture_tab_id'
        );
    }

    /**
     * Scope: Filter by competition using helper.
     */
    public function scopeByCompetition($query)
    {
        $competitionId = currentCompetitionId();
        return $query->where('competition_id', $competitionId);
    }
}
