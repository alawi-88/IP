<?php

namespace App\Services;

use App\Models\ProgramApplication;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\FormField;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAggregationService
{
    protected ?int $programId;
    protected array $runtimeFilters;
    protected int $cacheTtl = 600; // 10 minutes

    public function __construct(?int $programId = null, array $runtimeFilters = [])
    {
        $this->programId = $programId;
        $this->runtimeFilters = $runtimeFilters;
    }

    /**
     * Get aggregated data for an entire dashboard.
     */
    public function getDashboardData(Dashboard $dashboard): array
    {
        $widgets = $dashboard->widgets()->with('formField')->get();
        $results = [];

        foreach ($widgets as $widget) {
            $results[] = $this->getWidgetData($widget, $dashboard);
        }

        return $results;
    }

    /**
     * Get aggregated data for a single widget.
     */
    public function getWidgetData(DashboardWidget $widget, Dashboard $dashboard): array
    {
        $cacheKey = $this->buildCacheKey($widget, $dashboard);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($widget, $dashboard) {
            $rawData = $this->fetchRawData($dashboard->data_sources, $widget->parameter_key);
            $aggregated = $this->aggregate($rawData, $widget);

            return [
                'widget_id' => $widget->id,
                'parameter_key' => $widget->parameter_key,
                'label' => $widget->getFieldLabel(),
                'label_ar' => $widget->getFieldLabel('ar'),
                'aggregation_type' => $widget->aggregation_type,
                'visualization_type' => $widget->visualization_type,
                'data' => $aggregated,
                'configuration' => $widget->configuration ?? [],
            ];
        });
    }

    /**
     * Fetch raw submission values for a given parameter key from data sources.
     */
    protected function fetchRawData(array $dataSources, string $parameterKey): Collection
    {
        $allData = collect();

        foreach ($dataSources as $source) {
            $query = match ($source) {
                'applications' => $this->buildApplicationQuery($parameterKey),
                'projects' => $this->buildProjectQuery($parameterKey),
                default => null,
            };

            if ($query) {
                $allData = $allData->merge($query->get());
            }
        }

        return $allData;
    }

    /**
     * Build query for program_applications.form_submissions.
     */
    protected function buildApplicationQuery(string $parameterKey)
    {
        $query = ProgramApplication::query()
            ->select([
                'id',
                'program_id',
                'status',
                'form_id',
                'form_submissions',
                'created_at',
            ]);

        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        $this->applyRuntimeFilters($query, 'applications');

        return $query;
    }

    /**
     * Build query for projects.form_submissions.
     */
    protected function buildProjectQuery(string $parameterKey)
    {
        $query = Project::query()
            ->select([
                'id',
                'program_id',
                'status',
                'form_id',
                'form_submissions',
                'created_at',
            ]);

        if ($this->programId) {
            $query->where('program_id', $this->programId);
        }

        $this->applyRuntimeFilters($query, 'projects');

        return $query;
    }

    /**
     * Apply runtime filters to query.
     */
    protected function applyRuntimeFilters($query, string $source): void
    {
        if (!empty($this->runtimeFilters['program_id'])) {
            $query->where('program_id', $this->runtimeFilters['program_id']);
        }

        if (!empty($this->runtimeFilters['status'])) {
            $query->where('status', $this->runtimeFilters['status']);
        }

        if (!empty($this->runtimeFilters['date_from'])) {
            $query->where('created_at', '>=', $this->runtimeFilters['date_from']);
        }

        if (!empty($this->runtimeFilters['date_to'])) {
            $query->where('created_at', '<=', $this->runtimeFilters['date_to']);
        }
    }

    /**
     * Extract a field value from form_submissions SchemalessAttributes.
     */
    protected function extractFieldValue($record, string $parameterKey): mixed
    {
        $submissions = $record->form_submissions;

        if ($submissions === null) {
            return null;
        }

        // SchemalessAttributes: direct key access
        return $submissions->$parameterKey ?? null;
    }

    /**
     * Perform aggregation based on widget configuration.
     */
    protected function aggregate(Collection $rawData, DashboardWidget $widget): array
    {
        $parameterKey = $widget->parameter_key;

        // Extract values from form_submissions
        $values = $rawData->map(function ($record) use ($parameterKey) {
            return [
                'value' => $this->extractFieldValue($record, $parameterKey),
                'created_at' => $record->created_at,
                'status' => $record->status ?? null,
            ];
        })->filter(fn($item) => $item['value'] !== null);

        return match ($widget->aggregation_type) {
            'sum' => $this->aggregateSum($values),
            'average' => $this->aggregateAverage($values),
            'min' => $this->aggregateMin($values),
            'max' => $this->aggregateMax($values),
            'count' => $this->aggregateCount($values),
            'rate' => $this->aggregateRate($values),
            'count_distinct' => $this->aggregateCountDistinct($values),
            'group_by_period' => $this->aggregateGroupByPeriod($values, $widget),
            default => ['value' => 0, 'labels' => [], 'series' => []],
        };
    }

    protected function aggregateSum(Collection $values): array
    {
        $sum = $values->sum(fn($item) => is_numeric($item['value']) ? (float) $item['value'] : 0);
        return [
            'value' => round($sum, 2),
            'count' => $values->count(),
            'labels' => ['Total'],
            'series' => [$sum],
        ];
    }

    protected function aggregateAverage(Collection $values): array
    {
        $numericValues = $values->filter(fn($item) => is_numeric($item['value']));
        $avg = $numericValues->count() > 0
            ? $numericValues->avg(fn($item) => (float) $item['value'])
            : 0;

        return [
            'value' => round($avg, 2),
            'count' => $numericValues->count(),
            'labels' => ['Average'],
            'series' => [$avg],
        ];
    }

    protected function aggregateMin(Collection $values): array
    {
        $numericValues = $values->filter(fn($item) => is_numeric($item['value']));
        $min = $numericValues->count() > 0
            ? $numericValues->min(fn($item) => (float) $item['value'])
            : 0;

        return [
            'value' => round($min, 2),
            'count' => $numericValues->count(),
            'labels' => ['Minimum'],
            'series' => [$min],
        ];
    }

    protected function aggregateMax(Collection $values): array
    {
        $numericValues = $values->filter(fn($item) => is_numeric($item['value']));
        $max = $numericValues->count() > 0
            ? $numericValues->max(fn($item) => (float) $item['value'])
            : 0;

        return [
            'value' => round($max, 2),
            'count' => $numericValues->count(),
            'labels' => ['Maximum'],
            'series' => [$max],
        ];
    }

    protected function aggregateCount(Collection $values): array
    {
        // For choice fields, count occurrences of each option
        $grouped = $values->groupBy(function ($item) {
            $val = $item['value'];
            if (is_array($val)) {
                return implode(', ', $val);
            }
            return (string) $val;
        });

        $labels = $grouped->keys()->toArray();
        $series = $grouped->map->count()->values()->toArray();

        return [
            'value' => $values->count(),
            'count' => $values->count(),
            'labels' => $labels,
            'series' => $series,
        ];
    }

    protected function aggregateRate(Collection $values): array
    {
        $total = $values->count();
        if ($total === 0) {
            return ['value' => 0, 'count' => 0, 'labels' => [], 'series' => []];
        }

        // Flatten multi-select values
        $flattened = collect();
        foreach ($values as $item) {
            $val = $item['value'];
            if (is_array($val)) {
                foreach ($val as $v) {
                    $flattened->push($v);
                }
            } else {
                $flattened->push($val);
            }
        }

        $grouped = $flattened->countBy();
        $labels = $grouped->keys()->toArray();
        $series = $grouped->values()->map(fn($count) => round(($count / $total) * 100, 1))->toArray();

        return [
            'value' => $total,
            'count' => $total,
            'labels' => $labels,
            'series' => $series,
            'is_percentage' => true,
        ];
    }

    protected function aggregateCountDistinct(Collection $values): array
    {
        $unique = $values->pluck('value')->unique();
        $grouped = $values->groupBy(fn($item) => (string) $item['value']);

        return [
            'value' => $unique->count(),
            'count' => $values->count(),
            'labels' => $grouped->keys()->toArray(),
            'series' => $grouped->map->count()->values()->toArray(),
        ];
    }

    protected function aggregateGroupByPeriod(Collection $values, DashboardWidget $widget): array
    {
        $period = $widget->configuration['period'] ?? 'month';

        $format = match ($period) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'year' => 'Y',
            default => 'Y-m',
        };

        $grouped = $values->groupBy(function ($item) use ($format) {
            return $item['created_at'] ? $item['created_at']->format($format) : 'Unknown';
        })->sortKeys();

        return [
            'value' => $values->count(),
            'count' => $values->count(),
            'labels' => $grouped->keys()->toArray(),
            'series' => $grouped->map->count()->values()->toArray(),
        ];
    }

    /**
     * Get available form fields for given data sources.
     */
    public static function getAvailableFields(array $dataSources, ?int $programId = null): Collection
    {
        $formTypes = [];

        foreach ($dataSources as $source) {
            match ($source) {
                'applications' => $formTypes[] = 'registration',
                'projects' => $formTypes[] = 'project',
                default => null,
            };
        }

        $query = FormField::query()
            ->whereHas('form', function ($q) use ($formTypes, $programId) {
                $q->whereIn('type', $formTypes);
                if ($programId) {
                    $q->where('program_id', $programId);
                }
            })
            ->whereNotIn('type', ['section_header', 'paragraph'])
            ->with('form')
            ->orderBy('sort');

        return $query->get();
    }

    /**
     * Get tabular data for table-type widgets.
     */
    public function getTableData(DashboardWidget $widget, Dashboard $dashboard, int $perPage = 25): array
    {
        $rawData = $this->fetchRawData($dashboard->data_sources, $widget->parameter_key);

        $rows = $rawData->map(function ($record) use ($widget) {
            $value = $this->extractFieldValue($record, $widget->parameter_key);
            return [
                'id' => $record->id,
                'value' => is_array($value) ? implode(', ', $value) : $value,
                'status' => $record->status ?? '-',
                'date' => $record->created_at?->format('Y-m-d'),
            ];
        })->filter(fn($item) => $item['value'] !== null);

        return [
            'rows' => $rows->values()->toArray(),
            'total' => $rows->count(),
        ];
    }

    /**
     * Export dashboard data as array for CSV/Excel.
     */
    public function exportData(Dashboard $dashboard): array
    {
        $widgets = $dashboard->widgets()->with('formField')->get();
        $headers = ['ID'];
        $parameterKeys = [];

        foreach ($widgets as $widget) {
            $headers[] = $widget->getFieldLabel('en');
            $parameterKeys[] = $widget->parameter_key;
        }

        $headers[] = 'Status';
        $headers[] = 'Date';

        // Fetch all raw data
        $allRawData = collect();
        foreach ($dashboard->data_sources as $source) {
            $query = match ($source) {
                'applications' => $this->buildApplicationQuery($parameterKeys[0] ?? ''),
                'projects' => $this->buildProjectQuery($parameterKeys[0] ?? ''),
                default => null,
            };
            if ($query) {
                $allRawData = $allRawData->merge($query->get());
            }
        }

        $rows = [];
        foreach ($allRawData as $record) {
            $row = [$record->id];
            foreach ($parameterKeys as $key) {
                $value = $this->extractFieldValue($record, $key);
                $row[] = is_array($value) ? implode(', ', $value) : ($value ?? '');
            }
            $row[] = $record->status ?? '';
            $row[] = $record->created_at?->format('Y-m-d H:i');
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    protected function buildCacheKey(DashboardWidget $widget, Dashboard $dashboard): string
    {
        $filterHash = md5(json_encode($this->runtimeFilters));
        return "dashboard.{$dashboard->id}.widget.{$widget->id}.{$filterHash}";
    }

    /**
     * Clear cache for a dashboard.
     */
    public static function clearDashboardCache(int $dashboardId): void
    {
        Cache::flush(); // In production, use tagged cache or specific keys
    }
}
