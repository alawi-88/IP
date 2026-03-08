<?php

namespace App\Filament\Resources\CustomDashboardResource\Pages;

use App\Filament\Resources\CustomDashboardResource;
use App\Models\Dashboard;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListCustomDashboards extends ListRecords
{
    protected static string $resource = CustomDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('dashboard.create_dashboard')),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = Dashboard::query()
            ->where(function ($q) {
                $q->where('competition_id', currentCompetitionId())
                  ->orWhereNull('competition_id');
            });

        return [
            'all' => Tab::make(__('dashboard.all'))
                ->badge((clone $baseQuery)->count()),

            'active' => Tab::make(__('dashboard.status_active'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn ($query) => $query->active()),

            'archived' => Tab::make(__('dashboard.status_archived'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn ($query) => $query->archived()),
        ];
    }
}
