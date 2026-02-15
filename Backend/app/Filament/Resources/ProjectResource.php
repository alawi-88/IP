<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\EvaluationsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\JudgesRelationManager;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Project;
use App\Models\ProjectComment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Programs';


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'view' => Pages\ViewProject::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            JudgesRelationManager::class,
            EvaluationsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Projects cannot be edited - they can only be deleted, archived, or restored
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Project') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Project');
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

        // Check if user has "archive Project" permission
        return $user->can('archive Project');
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

        // Check if user has "restore Project" permission
        return $user->can('restore Project');
    }
}
