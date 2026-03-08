<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParticipantResource\Pages;
use App\Filament\Resources\ParticipantResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Participant;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ParticipantResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Users & Roles';

    protected static ?int $navigationSort = 1;


    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParticipants::route('/'),
            'view' => Pages\ViewParticipant::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Participants cannot be edited - they can only be deleted, archived, or restored
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Participant') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Participant');
    }
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete Participant');
    }

    public static function canArchive(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypasses restrictions
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has "archive Participant" permission
        return $user->can('archive Participant');
    }

    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin bypasses restrictions
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has "restore Participant" permission
        return $user->can('restore Participant');
    }
}
