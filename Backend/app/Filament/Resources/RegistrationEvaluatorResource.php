<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationEvaluatorResource\Pages;
use App\Models\RegistrationEvaluator;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class RegistrationEvaluatorResource extends Resource
{
    protected static ?string $model = RegistrationEvaluator::class;

    // Managed via Program Hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Forms & Configuration';
    protected static ?string $modelLabel = 'Registration Evaluator';
    protected static ?string $pluralModelLabel = 'Registration Evaluators';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return 'Evaluators';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrationEvaluators::route('/'),
            'create' => Pages\CreateRegistrationEvaluator::route('/create'),
            'view' => Pages\ViewRegistrationEvaluator::route('/{record}'),
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

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        return false;
    }
}
