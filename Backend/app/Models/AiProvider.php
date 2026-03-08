<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider_type',
        'api_key',
        'models',
        'default_model',
        'base_url',
        'max_tokens',
        'is_active',
        'priority',
        'cost_per_1k_tokens',
        'max_retries',
        'error_count',
        'total_requests',
        'total_tokens_used',
        'last_error',
        'last_error_at',
        'last_used_at',
        'auto_disable_threshold',
        'monthly_budget',
        'monthly_tokens_limit',
        'monthly_tokens_used',
        'monthly_spend',
        'budget_reset_at',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'models' => 'array',
        'is_active' => 'boolean',
        'cost_per_1k_tokens' => 'decimal:6',
        'last_error_at' => 'datetime',
        'last_used_at' => 'datetime',
        'monthly_budget' => 'decimal:2',
        'monthly_spend' => 'decimal:4',
        'budget_reset_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')
                     ->orderBy('cost_per_1k_tokens', 'asc');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('provider_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Record a successful API call.
     */
    public function recordSuccess(int $tokensUsed = 0): void
    {
        $this->resetMonthlyCountersIfNeeded();

        $estimatedCost = $tokensUsed > 0
            ? ($tokensUsed / 1000) * (float) $this->cost_per_1k_tokens
            : 0;

        $this->update([
            'error_count' => 0,
            'total_requests' => $this->total_requests + 1,
            'total_tokens_used' => $this->total_tokens_used + $tokensUsed,
            'monthly_tokens_used' => $this->monthly_tokens_used + $tokensUsed,
            'monthly_spend' => $this->monthly_spend + $estimatedCost,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Record a failed API call.
     */
    public function recordError(string $message): void
    {
        $this->update([
            'error_count' => $this->error_count + 1,
            'total_requests' => $this->total_requests + 1,
            'last_error' => $message,
            'last_error_at' => now(),
            'last_used_at' => now(),
        ]);

        if ($this->shouldAutoDisable()) {
            $this->disable();
        }
    }

    /**
     * Reset consecutive error count.
     */
    public function resetErrors(): void
    {
        $this->update([
            'error_count' => 0,
            'last_error' => null,
            'last_error_at' => null,
        ]);
    }

    /**
     * Check if the provider should be auto-disabled.
     */
    public function shouldAutoDisable(): bool
    {
        return $this->error_count >= $this->auto_disable_threshold;
    }

    /**
     * Disable the provider.
     */
    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the currently selected model name.
     */
    public function getSelectedModel(): string
    {
        return $this->default_model;
    }

    /**
     * Get provider type display label.
     */
    public function getProviderTypeLabel(): string
    {
        return match ($this->provider_type) {
            'anthropic' => 'Anthropic (Claude)',
            'openai' => 'OpenAI (GPT)',
            'gemini' => 'Google (Gemini)',
            default => ucfirst($this->provider_type),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Budget & Limits
    |--------------------------------------------------------------------------
    */

    public function getBudgetUsagePercentage(): ?float
    {
        if (!$this->monthly_budget || $this->monthly_budget <= 0) {
            return null;
        }
        return min(100, ($this->monthly_spend / $this->monthly_budget) * 100);
    }

    public function getTokenUsagePercentage(): ?float
    {
        if (!$this->monthly_tokens_limit || $this->monthly_tokens_limit <= 0) {
            return null;
        }
        return min(100, ($this->monthly_tokens_used / $this->monthly_tokens_limit) * 100);
    }

    public function isOverBudget(): bool
    {
        if ($this->monthly_budget && $this->monthly_spend >= $this->monthly_budget) {
            return true;
        }
        if ($this->monthly_tokens_limit && $this->monthly_tokens_used >= $this->monthly_tokens_limit) {
            return true;
        }
        return false;
    }

    public function resetMonthlyCountersIfNeeded(): void
    {
        if (!$this->budget_reset_at || $this->budget_reset_at->diffInDays(now()) >= 30) {
            $this->update([
                'monthly_tokens_used' => 0,
                'monthly_spend' => 0,
                'budget_reset_at' => now(),
            ]);
            $this->refresh();
        }
    }
}
