<?php

namespace App\Filament\Resources\VentureSectionConfigResource\Pages;

use App\Filament\Resources\VentureSectionConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVentureSectionConfig extends EditRecord
{
    protected static string $resource = VentureSectionConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
