<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentureTabConfig extends Model
{
    protected $fillable = [
        'tab_slug',
        'label_en',
        'label_ar',
        'icon',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    /**
     * Get section configs that belong to this tab via tab_slug.
     */
    public function sectionConfigs(): HasMany
    {
        return $this->hasMany(VentureSectionConfig::class, 'tab_slug', 'tab_slug');
    }

    /**
     * Scope: Get only visible tab configs.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Order tabs by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
