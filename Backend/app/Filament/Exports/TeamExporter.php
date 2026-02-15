<?php

namespace App\Filament\Exports;

use App\Models\Team;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class TeamExporter extends Exporter
{
    protected static ?string $model = Team::class;

    public static function getColumns(): array
    {
        // Teams - (Logo - Idea Description) sections are missing from the Excel sheet. & Change the text to “track“ in the excel sheet.
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('logo')->formatStateUsing(fn($record) => $record->logo ? Storage::url($record->logo) : null),
            ExportColumn::make('idea_description')->label('Idea Description'),
            ExportColumn::make('team leader')
                ->label('Team Leader')
                ->formatStateUsing(fn($record) => $record->members()->where('is_leader', true)->first()?->participant?->name),
            ExportColumn::make('members')
                ->formatStateUsing(fn($record) => $record->members->pluck('participant.serial_number')->join(', ')),
            ExportColumn::make('path.title')->label('Track'),
            ExportColumn::make('challenge.title')->label('Challenge'),
            ExportColumn::make('skills'),
            ExportColumn::make('contact_email')->label('Contact Email'),
            ExportColumn::make('is_published')->formatStateUsing(fn($record) => $record->is_published ? 'Yes' : 'No'),
            ExportColumn::make('created_at')
                ->label('Submitted At')
                ->formatStateUsing(fn($record) => $record->created_at?->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your team export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
