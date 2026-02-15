<?php

namespace App\Filament\Resources\RegistrationStepResource\Pages;

use App\Filament\Resources\RegistrationStepResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFormStep extends CreateRecord
{
    protected static string $resource = RegistrationStepResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
