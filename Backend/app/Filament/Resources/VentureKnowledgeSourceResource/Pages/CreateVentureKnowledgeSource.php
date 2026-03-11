<?php

namespace App\Filament\Resources\VentureKnowledgeSourceResource\Pages;

use App\Filament\Resources\VentureKnowledgeSourceResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;

class CreateVentureKnowledgeSource extends CreateRecord
{
    protected static string $resource = VentureKnowledgeSourceResource::class;

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
                ->rows(8),
            TextInput::make('url')
                ->url()
                ->maxLength(2048),
            TextInput::make('file_path')
                ->maxLength(512),
            TextInput::make('priority')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->default(true),
        ];
    }
}
