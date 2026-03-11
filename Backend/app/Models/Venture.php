<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Venture extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'competition_id',
        'participant_id',
        'title',
        'slug',
        'idea_prompt',
        'status',
        'viability_score',
        'industry',
        'target_market',
        'business_model',
        'metadata',
        'is_archived',
        'version',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_archived' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

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
        return $this->belongsTo(User::class, 'participant_id');
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
