<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Team;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class TeamResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Programs';


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Team') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        // Teams cannot be edited - they can only be deleted, archived, or restored
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Team');
    }

    public static function canArchive(Model $record): bool
    {
        return auth()->user()?->can('archive Team') && !$record->isArchived();
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore Team') && $record->isArchived();
    }
}
