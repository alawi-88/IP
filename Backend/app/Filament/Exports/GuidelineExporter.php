<?php

namespace App\Filament\Exports;

use App\Models\Guideline;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class GuidelineExporter extends Exporter
{
    protected static ?string $model = Guideline::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('competition.title')->label('Program'),
            ExportColumn::make('title')->label('Title'),
            ExportColumn::make('files')->label('Files')->getStateUsing(function ($record) {
                return $record->files->pluck('attachment')->map(function ($attachment) {
                    return Storage::url($attachment);
                })->join(', ');
            }),
            ExportColumn::make('created_at')->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your guideline export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
