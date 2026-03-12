<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureTabConfigResource\Pages;
use App\Filament\Resources\VentureTabConfigResource\RelationManagers\SectionConfigsRelationManager;
use App\Models\VentureTabConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureTabConfigResource extends Resource
{
    protected static ?string $model = VentureTabConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Tab Builder';

    protected static ?string $modelLabel = 'Tab Config';

    protected static ?string $pluralModelLabel = 'Tab Configs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tab Identity')
                    ->description('Define the tab slug, labels, and display settings')
                    ->schema([
                        Forms\Components\TextInput::make('tab_slug')
                            ->required()
                            ->unique('venture_tab_configs', 'tab_slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier (e.g., "strategic_frameworks"). Use snake_case.')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('label_en')
                            ->label('Label (English)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('label_ar')
                            ->label('Label (Arabic)')
                            ->maxLength(255)
                            ->columnSpan(1),
                    ])->columns(3),

                Forms\Components\Section::make('Appearance & Order')
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->label('Icon')
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
                            ->helperText('Search for an icon by name (e.g., "rocket", "chart", "user")')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->helperText('Lower number = appears first')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_visible')
                            ->default(true)
                            ->helperText('Hidden tabs will not appear in new ventures')
                            ->columnSpan(1),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SectionConfigsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentureTabConfigs::route('/'),
            'create' => Pages\CreateVentureTabConfig::route('/create'),
            'edit' => Pages\EditVentureTabConfig::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }
}
