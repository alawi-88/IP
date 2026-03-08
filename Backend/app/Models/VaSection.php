<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'startup_id',
        'section_key',
        'title_en',
        'title_ar',
        'completion_percentage',
        'last_edited_at',
    ];

    protected $casts = [
        'completion_percentage' => 'decimal:2',
        'last_edited_at' => 'datetime',
    ];

    /**
     * Get the startup that owns this section
     */
    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    /**
     * Get all pages in this section
     */
    public function vaPages(): HasMany
    {
        return $this->hasMany(VaPage::class)->orderBy('order', 'asc');
    }

    /**
     * Calculate completion based on pages
     */
    public function calculateCompletion(): void
    {
        $pages = $this->vaPages()->get();
        
        if ($pages->isEmpty()) {
            $this->completion_percentage = 0;
        } else {
            $avgCompletion = $pages->avg('completion_percentage');
            $this->completion_percentage = round($avgCompletion, 2);
        }
        
        $this->last_edited_at = now();
        $this->save();
        
        // Update parent startup completion
        $this->startup->calculateCompletion();
    }


    /**
     * Resolve route binding by ID or section_key
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->firstOrFail();
        }
        if (is_numeric($value)) {
            return $this->where("id", $value)->firstOrFail();
        }
        return $this->where("section_key", $value)->firstOrFail();
    }


    /**
     * Scope child route bindings (pages) to this section
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if (is_numeric($value)) {
            return $this->vaPages()->where("id", $value)->firstOrFail();
        }
        return $this->vaPages()->where("page_key", $value)->firstOrFail();
    }
}
