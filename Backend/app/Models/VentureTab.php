<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentureTab extends Model
{
    protected $fillable = [
        'venture_id',
        'tab_key',
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
     * Get the venture this tab belongs to.
     */
    public function venture(): BelongsTo
    {
        return $this->belongsTo(Venture::class);
    }

    /**
     * Get all sections in this tab.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(VentureSection::class, 'venture_tab_id');
    }

    /**
     * Scope: Get only visible tabs.
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
