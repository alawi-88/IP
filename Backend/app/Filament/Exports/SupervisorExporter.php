<?php

namespace App\Filament\Exports;

use App\Models\Supervisor;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SupervisorExporter extends Exporter
{
    protected static ?string $model = Supervisor::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('competitions')->label('Programs')
                ->formatStateUsing(fn ($record) => $record->competitions->map(fn ($competition) => $competition->title)->join(', ')),
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('role')
                ->formatStateUsing(fn($record) => $record->roles->pluck('name')->map(fn($role) => ucfirst($role))->join(', ')),
            ExportColumn::make('last_login_at')->formatStateUsing(fn ($record) => $record->last_login_at?->format('Y-m-d H:i') ?? 'Never'),
            ExportColumn::make('created_at')->formatStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your supervisor export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
