<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureSectionConfigResource\Pages;
use App\Models\VentureSectionConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureSectionConfigResource extends Resource
{
    protected static ?string $model = VentureSectionConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Section Builder';

    public static function form(Form $form): Form
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
                        'persona_cards' => 'Persona Cards',
                        'journey_timeline' => 'Journey Timeline',
                        'timeline' => 'Timeline',
                        'progress_bars' => 'Progress Bars',
                        'viability_score' => 'Viability Score',
                        'key_value' => 'Key Value',
                        'funnel_chart' => 'Funnel Chart',
                        'risk_matrix' => 'Risk Matrix',
                        'canvas_grid' => 'Canvas Grid',
                        'cost_table' => 'Cost Table',
                        'line_chart' => 'Line Chart',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentureSectionConfigs::route('/'),
            'create' => Pages\CreateVentureSectionConfig::route('/create'),
            'edit' => Pages\EditVentureSectionConfig::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }
}
