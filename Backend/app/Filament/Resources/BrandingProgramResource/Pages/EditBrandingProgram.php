<?php

namespace App\Filament\Resources\BrandingProgramResource\Pages;

use App\Filament\Resources\BrandingProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandingProgram extends EditRecord
{
    protected static string $resource = BrandingProgramResource::class;

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
