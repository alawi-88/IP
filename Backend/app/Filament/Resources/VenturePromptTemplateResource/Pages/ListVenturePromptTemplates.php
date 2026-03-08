<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVenturePromptTemplates extends ListRecords
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
