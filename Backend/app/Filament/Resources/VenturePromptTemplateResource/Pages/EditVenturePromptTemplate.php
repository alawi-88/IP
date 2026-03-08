<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVenturePromptTemplate extends EditRecord
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
