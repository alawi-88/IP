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
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_visible' => 'boolean',
    ];

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
     * Get the Filament form for managing venture section configs.
     */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('section_slug')
                    ->required()
                    ->unique('venture_section_configs', 'section_slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\Select::make('tab_slug')
                    ->required()
                    ->options([
                        'dashboard' => 'Dashboard',
                        'strategic_frameworks' => 'Strategic Frameworks',
                        'market_analysis' => 'Market Analysis',
                        'financial_projections' => 'Financial Projections',
                        'mvp_roadmap' => 'MVP Roadmap',
                        'risk_assessment' => 'Risk Assessment',
                        'go_to_market' => 'Go to Market',
                        'competitive_analysis' => 'Competitive Analysis',
                    ])
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\TextInput::make('label_en')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\TextInput::make('label_ar')
                    ->maxLength(255)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\TextInput::make('icon')
                    ->maxLength(255)
                    ->columnSpan(['sm' => 2, 'lg' => 1])
                    ->helperText('Icon class or name'),

                Forms\Components\ColorPicker::make('color')
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

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
                    ])
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\TextInput::make('sort_order')
                    ->integer()
                    ->default(0)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\Toggle::make('is_visible')
                    ->default(true)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\KeyValue::make('metadata')
                    ->columnSpan(['sm' => 2, 'lg' => 2])
                    ->helperText('Additional configuration as key-value pairs'),
            ]);
    }

    /**
     * Get the Filament table columns for displaying venture section configs.
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('section_slug')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('tab_slug')
                ->sortable()
                ->badge(),

            Tables\Columns\TextColumn::make('label_en')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('label_ar')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('component_type')
                ->sortable()
                ->badge(),

            Tables\Columns\TextColumn::make('sort_order')
                ->sortable()
                ->numeric(),

            Tables\Columns\ColorColumn::make('color'),

            Tables\Columns\IconColumn::make('is_visible')
                ->boolean()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
