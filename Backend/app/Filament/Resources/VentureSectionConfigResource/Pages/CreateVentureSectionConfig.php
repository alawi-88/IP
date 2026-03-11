<?php

namespace App\Filament\Resources\VentureSectionConfigResource\Pages;

use App\Filament\Resources\VentureSectionConfigResource;
use App\Models\VentureSectionConfig;
use Filament\Resources\Pages\CreateRecord;

class CreateVentureSectionConfig extends CreateRecord
{
    protected static string $resource = VentureSectionConfigResource::class;

    protected function getFormSchema(): array
    {
        return VentureSectionConfig::form();
    }
}
