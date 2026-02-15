<?php

namespace App\Filament\Resources\ProjectFormConfigResource\Pages;

use App\Filament\Resources\ProjectFormConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectFormConfig extends CreateRecord
{
    protected static string $resource = ProjectFormConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.project-form-configs.index');
    }
}
