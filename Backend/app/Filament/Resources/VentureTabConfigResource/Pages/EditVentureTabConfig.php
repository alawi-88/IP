<?php

namespace App\Filament\Resources\VentureTabConfigResource\Pages;

use App\Filament\Resources\VentureTabConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVentureTabConfig extends EditRecord
{
    protected static string $resource = VentureTabConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
