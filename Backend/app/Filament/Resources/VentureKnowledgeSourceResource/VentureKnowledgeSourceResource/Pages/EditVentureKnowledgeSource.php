<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use App\Filament\Resources\VentureKnowledgeSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVentureKnowledgeSource extends EditRecord
{
    protected static string $resource = VentureKnowledgeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
