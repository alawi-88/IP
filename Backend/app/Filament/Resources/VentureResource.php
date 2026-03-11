<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureResource\Pages;
use App\Models\Venture;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureResource extends Resource
{
    protected static ?string $model = Venture::class;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentures::route('/'),
            'view' => Pages\ViewVenture::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin', 'judge']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin', 'judge']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
