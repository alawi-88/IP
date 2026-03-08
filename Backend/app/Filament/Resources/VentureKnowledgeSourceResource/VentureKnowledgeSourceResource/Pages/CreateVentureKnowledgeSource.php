<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use App\Filament\Resources\VentureKnowledgeSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVentureKnowledgeSource extends CreateRecord
{
    protected static string $resource = VentureKnowledgeSourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
