<?php

namespace App\Filament\Resources\BrandingCompetitionResource\Pages;

use App\Filament\Resources\BrandingCompetitionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrandingCompetition extends CreateRecord
{
    protected static string $resource = BrandingCompetitionResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
