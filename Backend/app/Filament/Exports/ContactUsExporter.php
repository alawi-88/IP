<?php

namespace App\Filament\Exports;

use App\Models\ContactUs;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class ContactUsExporter extends Exporter
{
    protected static ?string $model = ContactUs::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('Submission ID'),
            ExportColumn::make('participant.name')->label('Participant Name'),
            ExportColumn::make('participant.email')->label('Participant Email'),
            ExportColumn::make('title')->label('Title'),
            ExportColumn::make('message'),
            ExportColumn::make('attachments')
                ->getStateUsing(fn($record) => collect($record->attachments)->map(fn($attachment) => Storage::url($attachment))->join(', ')),
            ExportColumn::make('created_at')
                ->label('Submitted At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your contact us export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
