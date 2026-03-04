<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Startup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'tagline',
        'description',
        'logo_path',
        'status',
        'sector',
        'stage',
        'founding_date',
        'team_size',
        'completion_percentage',
        'settings',
    ];

    protected $casts = [
        'founding_date' => 'date',
        'completion_percentage' => 'decimal:2',
        'settings' => 'json',
        'status' => 'string',
    ];

    /**
     * Get the user that owns this startup
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all VA sections for this startup
     */
    public function vaSections(): HasMany
    {
        return $this->hasMany(VaSection::class);
    }

    /**
     * Get all exports for this startup
     */
    public function exports(): HasMany
    {
        return $this->hasMany(StartupExport::class);
    }

    /**
     * Scope to filter startups by user
     */
    public function scopeForUser($query, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter active (non-archived) startups
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'archived')->whereNull('deleted_at');
    }

    /**
     * Scope to filter with completion percentage
     */
    public function scopeWithCompletion($query, $minPercentage = 0, $maxPercentage = 100)
    {
        return $query->whereBetween('completion_percentage', [$minPercentage, $maxPercentage]);
    }

    /**
     * Calculate overall completion from sections
     */
    public function calculateCompletion(): void
    {
        $sections = $this->vaSections()->get();
        
        if ($sections->isEmpty()) {
            $this->completion_percentage = 0;
        } else {
            $avgCompletion = $sections->avg('completion_percentage');
            $this->completion_percentage = round($avgCompletion, 2);
        }
        
        $this->save();
    }

    /**
     * Check if startup is within 30 days of soft deletion
     */
    public function canRestore(): bool
    {
        if (!$this->deleted_at) {
            return false;
        }

        $thirtyDaysAgo = now()->subDays(30);
        return $this->deleted_at->isAfter($thirtyDaysAgo);
    }
}
