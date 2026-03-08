<?php

namespace App\Models;

use App\Traits\Program\FilterByProgram;
use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Dashboard extends Model
{
    use HasFactory, HasTranslations, FilterByProgram, LogsActivity, HasActivityLog;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'program_id',
        'name',
        'description',
        'data_sources',
        'filters',
        'group_by',
        'sort_order',
        'created_by',
        'updated_by',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'data_sources' => 'array',
        'filters' => 'array',
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'name',
        'description',
        'data_sources',
        'filters',
        'group_by',
        'program_id',
    ];

    protected string $moduleName = 'Dashboard';
    protected string $logName = 'dashboard';

    // ─── Relationships ──────────────────────────────────────────────

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Archive Methods ────────────────────────────────────────────

    public function archive(): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function duplicate(): self
    {
        $clone = $this->replicate(['created_at', 'updated_at', 'widgets_count']);
        $clone->name = [
            'en' => ($this->getTranslation('name', 'en') ?? '') . ' - Copy',
            'ar' => ($this->getTranslation('name', 'ar') ?? '') . ' - نسخة',
        ];
        $clone->created_by = auth()->id();
        $clone->updated_by = null;
        $clone->save();

        foreach ($this->widgets as $widget) {
            $widgetClone = $widget->replicate(['created_at', 'updated_at']);
            $widgetClone->dashboard_id = $clone->id;
            $widgetClone->save();
        }

        return $clone;
    }

    public function getDataSourceLabels(): array
    {
        $labels = [
            'applications' => ['en' => 'Applications', 'ar' => 'الطلبات'],
            'projects' => ['en' => 'Projects', 'ar' => 'المشاريع'],
        ];

        return collect($this->data_sources ?? [])
            ->map(fn($source) => $labels[$source] ?? ['en' => $source, 'ar' => $source])
            ->toArray();
    }

    public static function getDataSourceOptions(): array
    {
        return [
            'applications' => __('dashboard.data_source_applications'),
            'projects' => __('dashboard.data_source_projects'),
        ];
    }

    public static function getVisualizationOptions(): array
    {
        return [
            'bar' => __('dashboard.viz_bar'),
            'pie' => __('dashboard.viz_pie'),
            'line' => __('dashboard.viz_line'),
            'table' => __('dashboard.viz_table'),
            'kpi' => __('dashboard.viz_kpi'),
        ];
    }

    public static function getAggregationOptionsForFieldType(string $fieldType): array
    {
        $aggregations = [
            'numeric' => [
                'sum' => __('dashboard.agg_sum'),
                'average' => __('dashboard.agg_average'),
                'min' => __('dashboard.agg_min'),
                'max' => __('dashboard.agg_max'),
                'count' => __('dashboard.agg_count'),
            ],
            'choice' => [
                'count' => __('dashboard.agg_count'),
                'rate' => __('dashboard.agg_rate'),
            ],
            'text' => [
                'count' => __('dashboard.agg_count'),
                'count_distinct' => __('dashboard.agg_count_distinct'),
            ],
            'date' => [
                'count' => __('dashboard.agg_count'),
                'group_by_period' => __('dashboard.agg_group_by_period'),
            ],
            'file' => [
                'count' => __('dashboard.agg_count'),
            ],
        ];

        $typeMap = [
            'number' => 'numeric',
            'float' => 'numeric',
            'rating' => 'numeric',
            'dropdown' => 'choice',
            'multi_select' => 'choice',
            'radio' => 'choice',
            'checkbox' => 'choice',
            'text' => 'text',
            'textarea' => 'text',
            'email' => 'text',
            'phone' => 'text',
            'url' => 'text',
            'date' => 'date',
            'time' => 'date',
            'file' => 'file',
            'image' => 'file',
            'section_header' => 'text',
            'paragraph' => 'text',
        ];

        $category = $typeMap[$fieldType] ?? 'text';

        return $aggregations[$category] ?? $aggregations['text'];
    }
}
