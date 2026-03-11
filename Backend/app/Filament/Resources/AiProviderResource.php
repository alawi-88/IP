<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiProviderResource\Pages;
use App\Models\AiProvider;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class AiProviderResource extends Resource
{
    protected static ?string $model = AiProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 3;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiProviders::route('/'),
            'create' => Pages\CreateAiProvider::route('/create'),
            'edit' => Pages\EditAiProvider::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
