<?php

namespace App\Filament\Resources\CustomDashboardResource\Pages;

use App\Filament\Resources\CustomDashboardResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCustomDashboard extends CreateRecord
{
    protected static string $resource = CustomDashboardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['competition_id'] = currentCompetitionId();
        $data['created_by'] = auth()->id();
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('dashboard.dashboard_created'))
            ->success();
    }
}
