<?php

namespace App\Filament\Exports;

use App\Models\Mentor;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class MentorExporter extends Exporter
{
    protected static ?string $model = Mentor::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('program.title')->label('Program'),
            ExportColumn::make('name'),
            ExportColumn::make('image')->formatStateUsing(fn($record) => $record->image ? Storage::url($record->image) : null),
            ExportColumn::make('experience'),
            ExportColumn::make('brief'),
            ExportColumn::make('link'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your mentor export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
