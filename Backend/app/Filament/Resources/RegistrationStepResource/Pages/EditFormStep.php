<?php

namespace App\Filament\Resources\RegistrationStepResource\Pages;

use App\Filament\Resources\RegistrationStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormStep extends EditRecord
{
    protected static string $resource = RegistrationStepResource::class;


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
