<?php

namespace App\Filament\Resources\AiProviderResource\Pages;

use App\Filament\Resources\AiProviderResource;
use App\Models\AiProvider;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProvider extends CreateRecord
{
    protected static string $resource = AiProviderResource::class;

    protected function getFormSchema(): array
    {
        return AiProvider::form();
    }
}
