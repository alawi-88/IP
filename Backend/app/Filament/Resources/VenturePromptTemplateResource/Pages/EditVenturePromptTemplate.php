<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use App\Models\VentureTabConfig;
use App\Models\VentureSectionConfig;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\EditRecord;

class EditVenturePromptTemplate extends EditRecord
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function getFormSchema(): array
    {
        // Build section options grouped by tab
        $sectionOptions = [];
        $tabConfigs = VentureTabConfig::ordered()->get();
        foreach ($tabConfigs as $tab) {
            $sections = VentureSectionConfig::where('tab_slug', $tab->tab_slug)
                ->orderBy('sort_order')
                ->get();
            foreach ($sections as $sec) {
                $sectionOptions[$tab->label_en][$sec->section_slug] = $sec->label_en . ' (' . $sec->section_slug . ')';
            }
        }

        return [
            Section::make('Section Mapping')
                ->description('Link this prompt template to a section')
                ->schema([
                    Select::make('section_slug')
                        ->label('Section')
                        ->required()
                        ->searchable()
                        ->options($sectionOptions)
                        ->helperText('The section this prompt template will be used for')
                        ->columnSpan(1),
                    TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Only active templates override the default prompt')
                        ->columnSpan(1),
                ])->columns(3),

            Section::make('System Prompt')
                ->description('Instructions that define the AI\'s role and behavior')
                ->schema([
                    Textarea::make('system_prompt')
                        ->label('')
                        ->required()
                        ->rows(8)
                        ->columnSpanFull()
                        ->helperText('Sets the AI role. Example: "You are an expert startup advisor. Respond with valid JSON only."'),
                ]),

            Section::make('User Prompt')
                ->description('The actual prompt sent to the AI. Use {venture_title}, {venture_idea}, {industry}, {target_market}, {business_model} as variables.')
                ->schema([
                    Textarea::make('user_prompt')
                        ->label('')
                        ->required()
                        ->rows(16)
                        ->columnSpanFull()
                        ->helperText('The main prompt. Variables: {venture_title}, {venture_idea}, {industry}, {target_market}, {business_model}'),
                ]),

            Section::make('AI Settings')
                ->schema([
                    TextInput::make('max_tokens')
                        ->numeric()
                        ->default(4096)
                        ->helperText('Maximum tokens in the AI response')
                        ->columnSpan(1),
                    TextInput::make('temperature')
                        ->numeric()
                        ->step(0.01)
                        ->default(0.70)
                        ->helperText('0 = deterministic, 1 = creative')
                        ->columnSpan(1),
                    Textarea::make('json_schema')
                        ->rows(4)
                        ->helperText('Optional JSON schema for structured output validation')
                        ->columnSpan(2),
                ])->columns(2),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
