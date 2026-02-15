<?php

namespace App\Filament\Resources\EvaluationStageConfigResource\Pages;

use App\Filament\Resources\EvaluationStageConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationStageConfigs extends ListRecords
{
    protected static string $resource = EvaluationStageConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
