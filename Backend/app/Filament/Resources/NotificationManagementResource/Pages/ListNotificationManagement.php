<?php

namespace App\Filament\Resources\NotificationManagementResource\Pages;

use App\Filament\Resources\NotificationManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationManagement extends ListRecords
{
    protected static string $resource = NotificationManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
