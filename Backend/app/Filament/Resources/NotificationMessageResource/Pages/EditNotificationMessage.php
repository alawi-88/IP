<?php

namespace App\Filament\Resources\NotificationMessageResource\Pages;

use App\Filament\Resources\NotificationMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationMessage extends EditRecord
{
    protected static string $resource = NotificationMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
