<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use App\Filament\Resources\VentureKnowledgeSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVentureKnowledgeSources extends ListRecords
{
    protected static string $resource = VentureKnowledgeSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
