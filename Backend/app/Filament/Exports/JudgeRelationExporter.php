<?php

namespace App\Filament\Exports;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class JudgeRelationExporter extends Exporter
{
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('project')
                ->label('Project')
                ->formatStateUsing(fn($record) => Project::find($record->pivot_project_id)->name),
            ExportColumn::make('name'),
            ExportColumn::make('evaluation_score')
                ->label('Evaluation Score')
                ->formatStateUsing(fn($record) => $record->pivot_evaluation_score . '%'),

            ExportColumn::make('created_at')
                ->label('Evaluated At')
                ->formatStateUsing(fn($record) => Carbon::parse($record->pivot_created_at)->toDateTimeString()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your judge relation export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
