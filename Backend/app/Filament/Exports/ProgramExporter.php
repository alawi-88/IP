<?php

namespace App\Filament\Exports;

use App\Models\Program;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class ProgramExporter extends Exporter
{
    protected static ?string $model = Program::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('title'),
            ExportColumn::make('registration_closed_date')->formatStateUsing(fn($record) => $record->registration_closed_date?->format('Y-m-d H:i:s')),
            ExportColumn::make('status')->formatStateUsing(fn($record) => $record->isClosed() ? 'Closed' : 'Open'),
            ExportColumn::make('about'),
            ExportColumn::make('terms_and_conditions'),
            ExportColumn::make('banner')->formatStateUsing(fn($record) => $record->banner ? Storage::url($record->banner) : null),
            ExportColumn::make('is_published')->formatStateUsing(fn($record) => $record->is_published ? 'Yes' : 'No'),
            ExportColumn::make('created_at')->formatStateUsing(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your program export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
