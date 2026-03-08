<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVenturePromptTemplate extends CreateRecord
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
