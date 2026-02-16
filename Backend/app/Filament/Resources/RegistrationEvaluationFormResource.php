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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Programs';
    protected static ?string $modelLabel = 'Evaluation Form';
    protected static ?string $pluralModelLabel = 'Evaluation Forms';

    protected static ?int $navigationSort = 5;

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
        return auth()->user()?->can('view CompetitionApplication') ?? false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($record->competition) {
            return $record->competition->canAccessProgram();
        }
        return false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('view CompetitionApplication') ?? false;
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
        if ($record->competition && !$record->competition->canAccessProgram()) return false;
        return $user->can('delete CompetitionApplication');
    }
}
