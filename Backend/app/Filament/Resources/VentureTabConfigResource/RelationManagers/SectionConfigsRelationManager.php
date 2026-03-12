<?php

namespace App\Filament\Resources\VentureTabConfigResource\RelationManagers;

use App\Filament\Resources\VentureSectionConfigResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionConfigsRelationManager extends RelationManager
{
    protected static string $relationship = 'sectionConfigs';

    protected static ?string $title = 'Sections';

    protected static ?string $recordTitleAttribute = 'label_en';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Section Identity')
                    ->schema([
                        Forms\Components\TextInput::make('section_slug')
                            ->required()
                            ->unique('venture_section_configs', 'section_slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier (e.g., "dashboard_viability"). Use snake_case.'),
                        Forms\Components\TextInput::make('label_en')
                            ->label('Label (English)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('label_ar')
                            ->label('Label (Arabic)')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('AI Prompt')
                    ->icon('heroicon-o-sparkles')
                    ->description('Define what the AI generates for this section. The prompt template is sent to the AI model when a venture is created.')
                    ->schema([
                        Forms\Components\Textarea::make('prompt_template')
                            ->label('Prompt Template')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull()
                            ->helperText('Use placeholders: {venture_title}, {venture_description}, {industry}, {target_market}, {business_model}')
                            ->placeholder("Analyze the competitive landscape for {venture_title}.\n\nVenture: {venture_description}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nReturn JSON with this exact structure (text_content renderer):\n{\"content\": \"Your analysis here...\"}"),
                        Forms\Components\Textarea::make('system_prompt')
                            ->label('System Prompt (optional)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('You are an expert startup advisor and business analyst. Respond with valid JSON only. No markdown, no explanation, no code fences.')
                            ->helperText('Override the default system prompt. Leave blank to use: "You are an expert startup advisor..."'),
                        Forms\Components\TextInput::make('max_tokens')
                            ->integer()
                            ->default(4096)
                            ->helperText('Max response length (tokens)'),
                        Forms\Components\TextInput::make('temperature')
                            ->numeric()
                            ->default(0.7)
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(2)
                            ->helperText('0 = deterministic, 2 = creative'),
                    ])->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Select::make('component_type')
                            ->required()
                            ->options([
                                'text_content' => 'Text Content',
                                'stat_cards' => 'Stat Cards',
                                'swot_grid' => 'SWOT Grid',
                                'pestel' => 'PESTEL',
                                'pricing_cards' => 'Pricing Cards',
                                'comparison_table' => 'Comparison Table',
                                'persona_card' => 'Persona Card',
                                'journey_timeline' => 'Journey Timeline',
                                'progress_bars' => 'Progress Bars',
                                'viability_score' => 'Viability Score',
                                'key_value' => 'Key Value',
                            ]),
                        Forms\Components\Select::make('icon')
                            ->searchable()
                            ->preload()
                            ->allowHtml()
                            ->options(fn () => VentureSectionConfigResource::getHeroiconOutlineOptions())
                            ->getSearchResultsUsing(function (string $search): array {
                                $icons = VentureSectionConfigResource::getHeroiconOutlineOptions();
                                $results = [];
                                $count = 0;
                                foreach ($icons as $key => $label) {
                                    if ($count >= 30) break;
                                    if (empty($search) || stripos($label, $search) !== false || stripos($key, $search) !== false) {
                                        try {
                                            $iconSvg = svg('heroicon-o-' . $key, 'w-4 h-4 inline-block mr-1')->toHtml();
                                        } catch (\Exception $e) {
                                            $iconSvg = '';
                                        }
                                        $results[$key] = $iconSvg . e($label);
                                        $count++;
                                    }
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (!$value) return null;
                                $icons = VentureSectionConfigResource::getHeroiconOutlineOptions();
                                $label = $icons[$value] ?? ucwords(str_replace('-', ' ', $value));
                                try {
                                    $iconSvg = svg('heroicon-o-' . $value, 'w-5 h-5 inline-block mr-1')->toHtml();
                                } catch (\Exception $e) {
                                    $iconSvg = '';
                                }
                                return $iconSvg . e($label);
                            })
                            ->helperText('Search for an icon by name'),
                        Forms\Components\ColorPicker::make('color'),
                        Forms\Components\TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->helperText('Lower = appears first'),
                        Forms\Components\Toggle::make('is_visible')
                            ->default(true),
                    ])->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->helperText('Additional configuration as key-value pairs'),
                    ])->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section_slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('label_en')
                    ->label('Label (EN)')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('component_type')
                    ->label('Component')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\IconColumn::make('has_prompt')
                    ->label('Prompt')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->prompt_template))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tab_slug'] = $this->ownerRecord->tab_slug;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
