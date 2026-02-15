<?php

namespace App\Filament\Resources\FormAiHintsResource\Pages;

use App\Filament\Resources\FormAiHintsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormAiHints extends ListRecords
{
    protected static string $resource = FormAiHintsResource::class;

    public function getHeading(): string
    {
        return 'AI Enhancements';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
