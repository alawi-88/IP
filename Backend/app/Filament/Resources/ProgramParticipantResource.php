<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramParticipantResource\Pages;
use App\Filament\Resources\ProgramParticipantResource\RelationManagers\CommentsRelationManager;
use App\Filament\Traits\CanBeDeletable;
use App\Models\CompetitionApplication;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ProgramParticipantResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = CompetitionApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Programs';

    protected static ?string $modelLabel = 'Program Participant';
    protected static ?string $pluralModelLabel = 'Program Participants';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Participants';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramParticipants::route('/'),
            'view' => Pages\ViewProgramParticipant::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::make(),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Participants cannot be edited - they can only be approved, rejected, deleted, or restored
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view CompetitionApplication') ?? false;
    }

    /**
     * Check if the user can view a specific participant
     * This prevents IDOR by ensuring users can only view participants from programs they have access to
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin can view all participants
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Check if user has access to the program (competition) this participant belongs to
        if ($record->competition) {
            return $record->competition->canAccessProgram();
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin can delete all participants
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $user->can('delete CompetitionApplication');
        }

        // Check if user has access to the program before allowing delete
        if ($record->competition && !$record->competition->canAccessProgram()) {
            return false;
        }

        return $user->can('delete CompetitionApplication');
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

        // Check if user has access to the program before allowing archive
        if ($record->competition && !$record->competition->canAccessProgram()) {
            return false;
        }

        // Check if user has "archive CompetitionApplication" permission
        return $user->can('archive CompetitionApplication');
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

        // Check if user has access to the program before allowing restore
        if ($record->competition && !$record->competition->canAccessProgram()) {
            return false;
        }

        // Check if user has "restore CompetitionApplication" permission
        return $user->can('restore CompetitionApplication');
    }
}

