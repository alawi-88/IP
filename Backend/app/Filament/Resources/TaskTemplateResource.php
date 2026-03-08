<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskTemplateResource\Pages;
use App\Models\TaskTemplate;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class TaskTemplateResource extends Resource
{
    protected static ?string $model = TaskTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'AI & Automation';
    protected static ?string $modelLabel = 'Task Template';
    protected static ?string $pluralModelLabel = 'Task Templates';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Task Templates';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaskTemplates::route('/'),
            'create' => Pages\CreateTaskTemplate::route('/create'),
            'edit' => Pages\EditTaskTemplate::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ProgramApplication') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('view ProgramApplication') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($record->program) return $record->program->canAccessProgram();
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }
}
