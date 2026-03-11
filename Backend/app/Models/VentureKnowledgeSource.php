<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentureKnowledgeSource extends Model
{
    protected $fillable = [
        'title',
        'type',
        'content',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
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
}
