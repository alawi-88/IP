<?php

namespace App\Filament\Resources\NotificationMessageResource\Pages;

use App\Filament\Resources\NotificationMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationMessage extends CreateRecord
{
    protected static string $resource = NotificationMessageResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
