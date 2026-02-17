<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SatisfactionResource\Pages;
use App\Filament\Resources\SatisfactionResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Satisfaction;
use Filament\Resources\Resource;

class SatisfactionResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Satisfaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-face-smile';
    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 7;

    protected static bool $shouldRegisterNavigation = false;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSatisfactions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
