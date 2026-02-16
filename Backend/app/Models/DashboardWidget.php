<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'form_field_id',
        'parameter_key',
        'aggregation_type',
        'visualization_type',
        'configuration',
        'sort_order',
    ];

    protected $casts = [
        'configuration' => 'array',
        'sort_order' => 'integer',
    ];

    public const AGGREGATION_TYPES = [
        'sum', 'average', 'min', 'max', 'count',
        'rate', 'count_distinct', 'group_by_period',
    ];

    public const VISUALIZATION_TYPES = [
        'bar', 'pie', 'line', 'table', 'kpi',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function getFieldLabel(?string $locale = null): string
    {
        if ($this->formField) {
            return $this->formField->getTranslation('label', $locale ?? app()->getLocale()) ?? $this->parameter_key;
        }

        return $this->parameter_key;
    }

    public function isChart(): bool
    {
        return in_array($this->visualization_type, ['bar', 'pie', 'line']);
    }

    public function isTable(): bool
    {
        return $this->visualization_type === 'table';
    }

    public function isKpi(): bool
    {
        return $this->visualization_type === 'kpi';
    }
}
