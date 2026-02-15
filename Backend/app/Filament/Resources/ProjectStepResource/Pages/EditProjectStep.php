<?php

namespace App\Filament\Resources\ProjectStepResource\Pages;

use App\Filament\Resources\ProjectStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectStep extends EditRecord
{
    protected static string $resource = ProjectStepResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
