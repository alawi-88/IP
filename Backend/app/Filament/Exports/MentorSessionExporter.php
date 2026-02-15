<?php

namespace App\Filament\Exports;

use App\Models\MentorSession;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class MentorSessionExporter extends Exporter
{
    protected static ?string $model = MentorSession::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('competition.title')
                ->label(__('sessions.competition')),
            ExportColumn::make('mentor.name')
                ->label(__('sessions.fields.mentor')),
            ExportColumn::make('participant.name')
                ->label(__('sessions.fields.participant'))
                ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
            ExportColumn::make('title')
                ->label(__('sessions.session_title')),
            ExportColumn::make('scheduled_at')
                ->label(__('sessions.session_date'))
                ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : 'N/A'),
            ExportColumn::make('duration_minutes')
                ->label(__('sessions.fields.duration'))
                ->formatStateUsing(function ($state) {
                    if (!$state) return 'N/A';
                    $hours = floor($state / 60);
                    $minutes = $state % 60;
                    if ($hours > 0) {
                        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
                    }
                    return "{$minutes}m";
                }),
            ExportColumn::make('status')
                ->label(__('sessions.session_status'))
                ->formatStateUsing(fn ($state) => MentorSession::STATUSES[$state] ?? $state),
            ExportColumn::make('video_tool')
                ->label(__('sessions.fields.video_tool'))
                ->formatStateUsing(fn ($state) => $state ? (MentorSession::VIDEO_TOOLS[$state] ?? $state) : 'N/A'),
            ExportColumn::make('rating')
                ->label(__('sessions.fields.rating'))
                ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : 'N/A'),
            ExportColumn::make('notes')
                ->label(__('sessions.fields.notes'))
                ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
            ExportColumn::make('started_at')
                ->label(__('sessions.fields.started_at'))
                ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : 'N/A'),
            ExportColumn::make('ended_at')
                ->label(__('sessions.fields.ended_at'))
                ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : 'N/A'),
            ExportColumn::make('created_at')
                ->label(__('sessions.fields.created_at'))
                ->formatStateUsing(fn ($state) => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('sessions.export_success', ['count' => number_format($export->successful_rows)]);
        
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . __('sessions.export_failed', ['count' => number_format($failedRowsCount)]);
        }

        return $body;
    }
}
