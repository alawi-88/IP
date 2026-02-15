<?php

namespace App\Filament\Resources\ProjectStepResource\Pages;

use App\Filament\Resources\ProjectStepResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectStep extends CreateRecord
{
    protected static string $resource = ProjectStepResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
