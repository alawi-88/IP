<?php

namespace App\Filament\Resources\TeamFormConfigResource\Pages;

use App\Filament\Resources\TeamFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamFormConfig extends CreateRecord
{
    protected static string $resource = TeamFormConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
