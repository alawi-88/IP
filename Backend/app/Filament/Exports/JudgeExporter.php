<?php

namespace App\Filament\Exports;

use App\Models\Judge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Infolists\Components\TextEntry;

class JudgeExporter extends Exporter
{
    protected static ?string $model = Judge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('experience_field'),

            ExportColumn::make('projects_assigned')
                ->getStateUsing(function ($record) {
                    $totalProjects = $record->projects()->count();
                    $completedCount = $record->projects()
                        ->wherePivot('evaluation_score', '>', 0)
                        ->count();
                    $pendingCount = $totalProjects - $completedCount;
                    return "{$pendingCount} Pending, {$completedCount} Completed";
                }),
            ExportColumn::make('programs')
                ->getStateUsing(function ($record) {
                    return $record->programs->map(function ($program) {
                        return $program->title;
                    })->join(', ');
                }),
            ExportColumn::make('phone_number'),
            ExportColumn::make('last_login_at')->formatStateUsing(fn($record) => $record->last_login_at?->format('d/m/Y H:i:s') ?? 'Never'),
            ExportColumn::make('created_at')->formatStateUsing(fn($record) => $record->created_at->format('d/m/Y H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your judge export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
