<?php

namespace App\Filament\Resources\MentorSessionResource\Pages;

use App\Filament\Resources\MentorSessionResource;
use App\Models\MentorSession;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListMentorSessions extends ListRecords
{
    protected static string $resource = MentorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admins cannot create sessions - only view and edit existing ones
            // Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = MentorSession::query();

        return [
            'all' => Tab::make(__('sessions.all_sessions'))
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn ($query) => clone $baseQuery),

            'upcoming' => Tab::make(__('sessions.upcoming_sessions'))
                ->badge((clone $baseQuery)->upcoming()->count())
                ->modifyQueryUsing(fn ($query) => (clone $baseQuery)->upcoming()),

            'past' => Tab::make(__('sessions.past_sessions'))
                ->badge((clone $baseQuery)->past()->count())
                ->modifyQueryUsing(fn ($query) => (clone $baseQuery)->past()),

            'canceled' => Tab::make(__('sessions.canceled_sessions'))
                ->badge((clone $baseQuery)->canceled()->count())
                ->modifyQueryUsing(fn ($query) => (clone $baseQuery)->canceled()),
        ];
    }
}
