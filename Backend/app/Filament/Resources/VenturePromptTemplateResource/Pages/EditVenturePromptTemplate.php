<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use Filament\Actions;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;

class EditVenturePromptTemplate extends EditRecord
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('section_key')
                ->required()
                ->maxLength(255),
            Textarea::make('prompt_template')
                ->required()
                ->rows(10),
            KeyValue::make('variables')
                ->keyLabel('Variable Name')
                ->valueLabel('Variable Description'),
            Toggle::make('is_active'),
            TextInput::make('version')
                ->numeric(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
