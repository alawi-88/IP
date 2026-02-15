<?php

namespace App\Filament\Resources\FormAiScoringConfigResource\Pages;

use App\Filament\Resources\FormAiScoringConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormAiScoringConfigs extends ListRecords
{
    protected static string $resource = FormAiScoringConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

