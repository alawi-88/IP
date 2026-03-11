<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenturePromptTemplate extends Model
{
    protected $fillable = [
        'section_key',
        'prompt_template',
        'variables',
        'is_active',
        'version',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
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
    public function scopeForSection($query, $key)
    {
        return $query->where('section_key', $key);
    }
}
