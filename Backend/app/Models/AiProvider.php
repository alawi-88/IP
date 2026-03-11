<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'model_name',
        'is_active',
        'priority',
        'max_tokens',
        'temperature',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'temperature' => 'decimal:2',
    ];

    /**
     * Get the venture sections that use this provider.
     */
    public function ventureSections(): HasMany
    {
        return $this->hasMany(VentureSection::class, 'ai_provider_id');
    }

    /**
     * Scope: Get only active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order providers by priority (highest first).
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
