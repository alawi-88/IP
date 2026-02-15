<?php

namespace App\Filament\Exports;

use App\Models\MentorSession;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class MentorshipAnalyticsExporter extends Exporter
{
    protected static ?string $model = MentorSession::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('analytics.session_id')),
            
            ExportColumn::make('competition.title')
                ->label(__('analytics.program'))
                ->formatStateUsing(function ($record) {
                    if (!$record->competition) {
                        return 'N/A';
                    }
                    return is_array($record->competition->title)
                        ? ($record->competition->title[app()->getLocale()] ?? $record->competition->title['en'] ?? 'N/A')
                        : ($record->competition->title ?? 'N/A');
                }),
            
            ExportColumn::make('mentor.name')
                ->label(__('analytics.mentor'))
                ->formatStateUsing(function ($record) {
                    if (!$record->mentor) {
                        return 'N/A';
                    }
                    return is_array($record->mentor->name)
                        ? ($record->mentor->name[app()->getLocale()] ?? $record->mentor->name['en'] ?? 'N/A')
                        : ($record->mentor->name ?? 'N/A');
                }),
            
            ExportColumn::make('participant.name')
                ->label(__('analytics.participant'))
                ->formatStateUsing(function ($record) {
                    if (!$record->participant) {
                        return 'N/A';
                    }
                    return is_array($record->participant->name)
                        ? ($record->participant->name[app()->getLocale()] ?? $record->participant->name['en'] ?? 'N/A')
                        : ($record->participant->name ?? 'N/A');
                }),
            
            ExportColumn::make('title')
                ->label(__('analytics.session_title')),
            
            ExportColumn::make('scheduled_at')
                ->label(__('analytics.scheduled_at'))
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : 'N/A'),
            
            ExportColumn::make('duration_minutes')
                ->label(__('analytics.duration_minutes')),
            
            ExportColumn::make('status')
                ->label(__('analytics.status'))
                ->formatStateUsing(fn ($state) => __("sessions.status.{$state}") ?? $state),
            
            ExportColumn::make('created_at')
                ->label(__('analytics.created_at'))
                ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m-d H:i:s') : 'N/A'),
        ];
    }

    protected function applyFilters(Builder $query, ?array $data): Builder
    {
        if (!$data) {
            return $query;
        }

        if (isset($data['competition_id'])) {
            $query->where('competition_id', $data['competition_id']);
        }

        if (isset($data['mentor_id'])) {
            $query->where('mentor_id', $data['mentor_id']);
        }

        if (isset($data['start_date'])) {
            $query->whereDate('scheduled_at', '>=', Carbon::parse($data['start_date'])->startOfDay());
        }

        if (isset($data['end_date'])) {
            $query->whereDate('scheduled_at', '<=', Carbon::parse($data['end_date'])->endOfDay());
        }

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('analytics.export_completed', [
            'count' => number_format($export->successful_rows)
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . __('analytics.export_failed_rows', [
                'count' => number_format($failedRowsCount)
            ]);
        }

        return $body;
    }
}

