<?php

namespace App\Filament\Resources\BrandingProgramResource\Pages;

use App\Filament\Resources\BrandingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrandingProgram extends CreateRecord
{
    protected static string $resource = BrandingProgramResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
