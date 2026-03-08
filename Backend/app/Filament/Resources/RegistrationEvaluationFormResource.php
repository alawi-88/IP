<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationEvaluationFormResource\Pages;
use App\Filament\Resources\RegistrationEvaluationFormResource\RelationManagers\CriteriaRelationManager;
use App\Models\RegistrationEvaluationForm;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class RegistrationEvaluationFormResource extends Resource
{
    protected static ?string $model = RegistrationEvaluationForm::class;

    // Managed via Program Hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Forms & Configuration';
    protected static ?string $modelLabel = 'Evaluation Form';
    protected static ?string $pluralModelLabel = 'Evaluation Forms';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return 'Registration Evaluation';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrationEvaluationForms::route('/'),
            'create' => Pages\CreateRegistrationEvaluationForm::route('/create'),
            'edit' => Pages\EditRegistrationEvaluationForm::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CriteriaRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ProgramApplication') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($record->program) {
            return $record->program->canAccessProgram();
        }
        return false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('view ProgramApplication') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($record->program && !$record->program->canAccessProgram()) return false;
        return $user->can('delete ProgramApplication');
    }
}
