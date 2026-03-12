<?php

namespace App\Models;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class VentureSectionConfig extends Model
{
    protected $fillable = [
        'section_slug',
        'tab_slug',
        'label_en',
        'label_ar',
        'icon',
        'color',
        'component_type',
        'sort_order',
        'is_visible',
        'metadata',
        'prompt_template',
        'system_prompt',
        'max_tokens',
        'temperature',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_visible' => 'boolean',
        'max_tokens' => 'integer',
        'temperature' => 'decimal:2',
    ];

    /**
     * Get the tab config this section belongs to.
     */
    public function tabConfig()
    {
        return $this->belongsTo(VentureTabConfig::class, 'tab_slug', 'tab_slug');
    }

    /**
     * Check if this section has a custom prompt template defined.
     */
    public function hasPromptTemplate(): bool
    {
        return !empty($this->prompt_template);
    }

    /**
     * Scope: Get only active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Filter by tab slug.
     */
    public function scopeForTab($query, $tabSlug)
    {
        return $query->where('tab_slug', $tabSlug);
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get tab options dynamically from the database.
     */
    public static function getTabOptions(): array
    {
        return VentureTabConfig::ordered()
            ->pluck('label_en', 'tab_slug')
            ->toArray();
    }

    /**
     * Get the Filament form for managing venture section configs.
     */
    public static function form(Forms\Form $form): Forms\Form
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

                        Forms\Components\Select::make('tab_slug')
                            ->required()
                            ->options(fn () => static::getTabOptions())
                            ->searchable(),

                        Forms\Components\TextInput::make('label_en')
                            ->label('Label (English)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('label_ar')
                            ->label('Label (Arabic)')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Select::make('component_type')
                            ->required()
                            ->options([
                                'text_content' => 'Text Content',
                                'stat_cards' => 'Stat Cards',
                                'swot_grid' => 'SWOT Grid',
                                'pricing_cards' => 'Pricing Cards',
                                'comparison_table' => 'Comparison Table',
                                'persona_card' => 'Persona Card',
                                'journey_timeline' => 'Journey Timeline',
                                'progress_bars' => 'Progress Bars',
                                'viability_score' => 'Viability Score',
                                'key_value' => 'Key Value',
                            ]),

                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Heroicon name (e.g., "chart-bar", "rocket-launch")'),

                        Forms\Components\ColorPicker::make('color'),

                        Forms\Components\TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->helperText('Lower = appears first'),

                        Forms\Components\Toggle::make('is_visible')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('AI Prompt Configuration')
                    ->description('Define how the AI generates content for this section. Leave blank to use the legacy hardcoded prompt (if one exists for this slug).')
                    ->schema([
                        Forms\Components\Textarea::make('prompt_template')
                            ->label('User Prompt Template')
                            ->rows(8)
                            ->helperText('The main instruction sent to the AI. Use {venture_title}, {venture_description}, {industry}, {target_market}, {business_model} as placeholders.')
                            ->placeholder("Analyze the competitive landscape for {venture_title}.\n\nVenture: {venture_description}\nIndustry: {industry}\nTarget Market: {target_market}\n\nProvide a detailed analysis including..."),

                        Forms\Components\Textarea::make('system_prompt')
                            ->label('System Prompt (optional)')
                            ->rows(4)
                            ->helperText('Optional system-level instructions for the AI. If blank, the default system prompt is used.')
                            ->placeholder('You are a startup strategy consultant. Respond with structured JSON...'),

                        Forms\Components\TextInput::make('max_tokens')
                            ->integer()
                            ->default(2000)
                            ->helperText('Maximum response length (tokens). Default: 2000'),

                        Forms\Components\TextInput::make('temperature')
                            ->numeric()
                            ->default(0.7)
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(2)
                            ->helperText('Creativity level: 0 = deterministic, 1 = balanced, 2 = very creative. Default: 0.7'),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->helperText('Additional configuration as key-value pairs'),
                    ])->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Get the Filament table columns for displaying venture section configs.
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('section_slug')
                ->label('Slug')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('tab_slug')
                ->label('Tab')
                ->sortable()
                ->badge(),

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

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
