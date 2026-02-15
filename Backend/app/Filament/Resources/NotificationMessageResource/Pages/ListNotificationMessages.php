<?php

namespace App\Filament\Resources\NotificationMessageResource\Pages;

use App\Filament\Resources\NotificationMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationMessages extends ListRecords
{
    protected static string $resource = NotificationMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
