<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureSectionConfigResource\Pages;
use App\Models\VentureSectionConfig;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureSectionConfigResource extends Resource
{
    protected static ?string $model = VentureSectionConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Section Builder';

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
