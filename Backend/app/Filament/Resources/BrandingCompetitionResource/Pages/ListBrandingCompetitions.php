<?php

namespace App\Filament\Resources\BrandingCompetitionResource\Pages;

use App\Filament\Resources\BrandingCompetitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrandingCompetitions extends ListRecords
{
    protected static string $resource = BrandingCompetitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
