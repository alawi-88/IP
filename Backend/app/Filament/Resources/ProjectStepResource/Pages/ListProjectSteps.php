<?php

namespace App\Filament\Resources\ProjectStepResource\Pages;

use App\Filament\Resources\ProjectStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectSteps extends ListRecords
{
    protected static string $resource = ProjectStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
