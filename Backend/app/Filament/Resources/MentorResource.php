<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentorResource\Pages;
use App\Filament\Resources\MentorResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Mentor;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class MentorResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Mentor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Users & Roles';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMentors::route('/'),
            'create' => Pages\CreateMentor::route('/create'),
            'view' => Pages\ViewMentor::route('/{record}'),
            'edit' => Pages\EditMentor::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Mentor') ?? false;
    }

    public static function canCreate(): bool
    {
        // if (! auth()->user()?->can('create Mentor')) {
        //     return false;
        // }

       // return ! empty(currentProgramId());
       return true;
    }

    public static function canEdit(Model $record): bool
    {
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return auth()->user()?->can('update Mentor');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Mentor');
    }

    public static function canArchive(Model $record): bool
    {
        return auth()->user()?->can('archive Mentor') && !$record->isArchived();
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore Mentor') && $record->isArchived();
    }
}
