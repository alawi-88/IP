<?php

namespace App\Filament\Exports;

use App\Models\Event;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class EventExporter extends Exporter
{
    protected static ?string $model = Event::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('title'),
            ExportColumn::make('brief'),
            ExportColumn::make('location'),
            ExportColumn::make('date')->formatStateUsing(fn ($record) => $record->date->format('Y-m-d')),
            ExportColumn::make('time')->formatStateUsing(fn ($record) => $record->time->format('H:i')),
            ExportColumn::make('badge'),
            ExportColumn::make('speaker_photo')->formatStateUsing(fn ($record) => $record->speaker_photo ? Storage::url($record->speaker_photo) : null),
            ExportColumn::make('speaker_name'),
            ExportColumn::make('speaker_experience'),
            ExportColumn::make('speaker_brief'),
            ExportColumn::make('event_link'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your event export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
