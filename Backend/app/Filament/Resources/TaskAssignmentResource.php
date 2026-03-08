<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskAssignmentResource\Pages;
use App\Filament\Resources\TaskAssignmentResource\RelationManagers\SubmissionsRelationManager;
use App\Filament\Resources\TaskAssignmentResource\RelationManagers\CommentsRelationManager;
use App\Models\TaskAssignment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class TaskAssignmentResource extends Resource
{
    protected static ?string $model = TaskAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'AI & Automation';
    protected static ?string $modelLabel = 'Task Assignment';
    protected static ?string $pluralModelLabel = 'Task Assignments';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return 'Tasks';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaskAssignments::route('/'),
            'create' => Pages\CreateTaskAssignment::route('/create'),
            'view' => Pages\ViewTaskAssignment::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SubmissionsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view CompetitionApplication') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('view CompetitionApplication') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($record->competition) return $record->competition->canAccessProgram();
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        return false;
    }
}
