<?php

namespace App\Filament\Resources\NotificationManagementResource\Pages;

use App\Filament\Resources\NotificationManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationManagement extends EditRecord
{
    protected static string $resource = NotificationManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
