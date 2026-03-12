<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VentureSection extends Model
{
    protected $appends = ['display_config'];

    protected $fillable = [
        'venture_id',
        'venture_tab_id',
        'slug',
        'label_en',
        'label_ar',
        'content',
        'content_ar',
        'prompt_sent',
        'raw_response',
        'status',
        'error_message',
        'sort_order',
        'is_visible',
        'component_type',
        'ai_provider_id',
        'tokens_used',
        'estimated_cost',
        'generation_attempts',
        'generated_at',
    ];

    protected $casts = [
        'content' => 'array',
        'content_ar' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the tab this section belongs to.
     */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(VentureTab::class, 'venture_tab_id');
    }

    /**
     * Get the AI provider used for this section.
     */
    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    /**
     * Get all versions of this section.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(VentureVersion::class, 'venture_section_id');
    }

    /**
     * Get the display configuration for this section.
     * Uses accessor instead of relationship since it's by section_key, not FK.
     */
    public function getDisplayConfigAttribute()
    {
        return VentureSectionConfig::where('section_slug', $this->slug)->first();
    }

    /**
     * Scope: Get only visible sections.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Get only completed sections.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get only failed sections.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
