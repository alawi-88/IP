<?php

namespace App\Models;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class VentureSectionConfig extends Model
{
    protected $fillable = [
        'section_key',
        'tab_key',
        'label_en',
        'label_ar',
        'icon',
        'color',
        'component_type',
        'display_order',
        'default_prompt',
        'is_active',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Get only active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by tab key.
     */
    public function scopeForTab($query, $tabKey)
    {
        return $query->where('tab_key', $tabKey);
    }

    /**
     * Scope: Order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Get the Filament form for managing venture section configs.
     */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('section_key')
                    ->required()
                    ->unique('venture_section_configs', 'section_key', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\Select::make('tab_key')
                    ->required()
                    ->options([
                        'dashboard' => 'Dashboard',
                        'strategic_frameworks' => 'Strategic Frameworks',
                        'path_to_mvp' => 'Path to MVP',
                        'usp' => 'USP',
                        'customer_persona' => 'Customer Persona',
                        'finances' => 'Finances',
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

                Forms\Components\TextInput::make('display_order')
                    ->integer()
                    ->default(0)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\Textarea::make('default_prompt')
                    ->columnSpan(['sm' => 2, 'lg' => 2])
                    ->rows(5),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->columnSpan(['sm' => 2, 'lg' => 1]),

                Forms\Components\KeyValue::make('config')
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
            Tables\Columns\TextColumn::make('section_key')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('tab_key')
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

            Tables\Columns\TextColumn::make('display_order')
                ->sortable()
                ->numeric(),

            Tables\Columns\ColorColumn::make('color'),

            Tables\Columns\IconColumn::make('is_active')
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
