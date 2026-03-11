<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenturePromptTemplate extends Model
{
    protected $fillable = [
        'section_slug',
        'label',
        'system_prompt',
        'user_prompt',
        'json_schema',
        'is_active',
        'max_tokens',
        'temperature',
    ];

    protected $casts = [
        'json_schema' => 'array',
        'is_active' => 'boolean',
        'temperature' => 'decimal:2',
    ];

    /**
     * Scope: Get only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get templates for a specific section.
     */
    public function scopeForSection($query, $slug)
    {
        return $query->where('section_slug', $slug);
    }
}
