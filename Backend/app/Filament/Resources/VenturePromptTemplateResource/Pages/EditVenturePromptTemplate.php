<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use Filament\Actions;
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
            TextInput::make('section_slug')
                ->required()
                ->maxLength(255),
            TextInput::make('label')
                ->required()
                ->maxLength(255),
            Textarea::make('system_prompt')
                ->required()
                ->rows(6),
            Textarea::make('user_prompt')
                ->required()
                ->rows(6),
            Textarea::make('json_schema')
                ->rows(4)
                ->helperText('JSON schema for structured output'),
            Toggle::make('is_active'),
            TextInput::make('max_tokens')
                ->numeric(),
            TextInput::make('temperature')
                ->numeric()
                ->step(0.01),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
