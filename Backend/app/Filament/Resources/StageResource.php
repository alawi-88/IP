<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StageResource\Pages;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Stage;
use Filament\Resources\Resource;

class StageResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Stage::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 9;
    protected static bool $shouldRegisterNavigation = false;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStages::route('/'),
            'create' => Pages\CreateStage::route('/create'),
            'view' => Pages\ViewStage::route('/{record}'),
            'edit' => Pages\EditStage::route('/{record}/edit'),
        ];
    }
}
