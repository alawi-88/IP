<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentureKnowledgeSource extends Model
{
    protected $fillable = [
        'title',
        'type',
        'content',
        'url',
        'file_path',
        'priority',
        'is_active',
        'applicable_sections',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'applicable_sections' => 'array',
    ];

    /**
     * Scope: Get only active knowledge sources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Order by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
