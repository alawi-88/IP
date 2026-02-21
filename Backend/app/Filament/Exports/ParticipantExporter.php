<?php

namespace App\Filament\Exports;

use App\Models\Participant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ParticipantExporter extends Exporter
{
    protected static ?string $model = Participant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('programs')->label('Programs')
            ->formatStateUsing(fn ($record) => $record->applications->map(fn ($application) => $application->program->title)->join(', ')),
            ExportColumn::make('serial_number'),
            ExportColumn::make('name'),
            ExportColumn::make('phone'),
            ExportColumn::make('nationality.name'),
            ExportColumn::make('date_of_birth')->formatStateUsing(fn ($record) => $record->date_of_birth->format('Y-m-d')),
            ExportColumn::make('gender')->formatStateUsing(fn ($record) => __('participant.' . $record->gender)),
            ExportColumn::make('email'),
            ExportColumn::make('email_verified_at')->formatStateUsing(fn ($record) => isset($record->email_verified_at) ? 'Yes' : 'No'),
            ExportColumn::make('applications')->formatStateUsing(fn ($record) => $record->applications->count()),
            ExportColumn::make('last_login_at')->formatStateUsing(fn ($record) => $record->last_login_at?->format('Y-m-d H:i') ?? 'Never'),
            ExportColumn::make('is_active')->formatStateUsing(fn ($record) => $record->is_active ? 'Yes' : 'No'),
            ExportColumn::make('created_at')
                ->label('Registration Date')
                ->formatStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i')),
            ExportColumn::make('country.name'),
            ExportColumn::make('residenceCity.name'),
            ExportColumn::make('educational_background')->formatStateUsing(fn ($record) => __('participant.' . $record->educational_background)),
            ExportColumn::make('current_role')->formatStateUsing(fn ($record) => __('participant.' . $record->current_role)),
            ExportColumn::make('place_of_work_study'),
            ExportColumn::make('years_of_experience')->formatStateUsing(fn ($record) => __('participant.' . $record->years_of_experience)),
            ExportColumn::make('experience_or_skills'),
            ExportColumn::make('key_achievements'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your participant export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
