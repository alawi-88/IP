<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Filament\Resources\CompetitionResource\RelationManagers\StagesRelationManager;
use App\Filament\Resources\CompetitionResource\RelationManagers\TabsRelationManager;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Competition;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CompetitionResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Competition::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Programs';

    protected static ?string $navigationLabel = 'Programs List';

    protected static ?int $navigationSort = 1;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            // All editing is now done in ManageCompetition (single unified page)
            'view' => Pages\ManageCompetition::route('/{record}'),
            'edit' => Pages\ManageCompetition::route('/{record}/edit'),
            'manage' => Pages\ManageCompetition::route('/{record}/manage'),
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema(Competition::form());
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                     //
            ])
            ->filters([
                     //
            ])
            ->actions([
                        //
            ])
            ->bulkActions([
                     //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TabsRelationManager::make(),
            StagesRelationManager::make(),
        ];
    }
    public static function getPluralLabel(): ?string
    {
        return 'Programs';   // Sidebar & index page
    }

    public static function getLabel(): ?string
    {
        return 'Program';    // Single record label
    }
    public static function getNavigationLabel(): string
    {
        return 'Programs';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Program') ?? false;
    }

    /**
     * Check if the user can view a specific program (competition)
     * This prevents IDOR by ensuring users can only view programs they have access to
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Use the model's canAccessProgram method to check authorization
        return $record->canAccessProgram();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }

        // Super admin bypasses restrictions
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // First check if user has access to this program (prevents IDOR)
        if (!$record->canAccessProgram()) {
            return false;
        }

        // Get competitions where the user is the creator via user_competitions table
        $createdCompetitionIds = $user->competitions()
            ->wherePivot('user_id', $user->id)
            ->pluck('competitions.id')
            ->toArray();

        if (in_array($record->id, $createdCompetitionIds) && $user->can('update Program')) {
            return true;
        }

        // Check if the user is assigned to this competition
        $isAssigned = $user->competitions()->where('competitions.id', $record->id)->exists();
        if ($isAssigned) {
            return false; // assigned users cannot edit
        }

        // Finally: check if user has "update Program" permission
        return false;
    }


    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }
        
        // Super admin bypasses restrictions
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // First check if user has access to this program (prevents IDOR)
        if (!$record->canAccessProgram()) {
            return false;
        }
        
        // User must have "delete Program" permission AND access to the program
        return $user->can('delete Program');
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

        // First check if user has access to this program (prevents IDOR)
        if (!$record->canAccessProgram()) {
            return false;
        }

        // Check if user has "archive Program" permission
        return $user->can('archive Program');
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

        // First check if user has access to this program (prevents IDOR)
        if (!$record->canAccessProgram()) {
            return false;
        }

        // Check if user has "restore Program" permission
        return $user->can('restore Program');
    }

}
