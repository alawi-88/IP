<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;

class EditVentureKnowledgeSource extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\VentureKnowledgeSourceResource';

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->required()
                ->options([
                    'industry_report' => 'Industry Report',
                    'market_data' => 'Market Data',
                    'template' => 'Template',
                    'methodology' => 'Methodology',
                ]),
            Textarea::make('content')
                ->required()
                ->rows(8),
            KeyValue::make('metadata')
                ->keyLabel('Key')
                ->valueLabel('Value'),
            Toggle::make('is_active'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
