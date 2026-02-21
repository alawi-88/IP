<?php

namespace App\Filament\Resources\BrandingProgramResource\Pages;

use App\Filament\Resources\BrandingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrandingPrograms extends ListRecords
{
    protected static string $resource = BrandingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
