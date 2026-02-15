<?php

namespace App\Filament\Resources\RegistrationStepResource\Pages;

use App\Filament\Resources\RegistrationStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormSteps extends ListRecords
{
    protected static string $resource = RegistrationStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
