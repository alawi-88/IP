<?php

namespace App\Filament\Resources\BrandingCompetitionResource\Pages;

use App\Filament\Resources\BrandingCompetitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandingCompetition extends EditRecord
{
    protected static string $resource = BrandingCompetitionResource::class;

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
