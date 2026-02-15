<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EvaluationRelationExporter extends Exporter
{
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('project')
                ->label('Project')
                ->formatStateUsing(fn($record) => $record->judgeProject->project->name),

            ExportColumn::make('judge')
                ->label('Judge')
                ->formatStateUsing(fn($record) => $record->judgeProject->judge->name),

            ExportColumn::make('average_score')
                ->label('Average Score')
                ->formatStateUsing(fn($record) => $record->judgeProject->project->total_score . '%'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your evaluation relation export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
