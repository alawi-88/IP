<?php

namespace App\Filament\Exports;

use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogExporter extends Exporter
{
    public static function getModel(): string
    {
        return Activity::class;
    }

    public function getFormats(): array
    {
        return [ExportFormat::Csv, ExportFormat::Xlsx];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('#'),
            ExportColumn::make('log_name')->label('Log'),
            ExportColumn::make('event'),
            ExportColumn::make('description')->limit(120),
            ExportColumn::make('causer.name')
                ->label('Performer')
                ->formatStateUsing(fn($state) => $state ?? '—'),
            ExportColumn::make('created_at')
                ->label('Creation Date')
                ->formatStateUsing(fn($state) => $state->format('Y-m-d H:i:s')),
        ];
    }

    protected function applyFilters(Builder $query, ?array $data): Builder
    {
        if (!$data) {
            return $query;
        }

        $scope = $data['scope'] ?? 'visible';

        return match ($scope) {
            'date_range' => $this->applyDateRange($query, $data),
            'competition' => $this->applyCompetitionFilter($query, $data),
            default => $query,
        };
    }

    private function applyDateRange(Builder $query, array $data): Builder
    {
        if (isset($data['from_date'], $data['to_date'])) {
            return $query->whereBetween('created_at', [
                Carbon::parse($data['from_date'])->startOfDay(),
                Carbon::parse($data['to_date'])->endOfDay(),
            ]);
        }
        return $query;
    }

    private function applyCompetitionFilter(Builder $query, array $data): Builder
    {
        if (isset($data['competition_id'])) {
            return $query->where('properties->attributes->competition_id', $data['competition_id']);
        }
        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Your log export is ready ({$export->successful_rows} rows).";
    }
}
