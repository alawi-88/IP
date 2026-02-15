<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JudgeResource\Pages;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Judge;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class JudgeResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Judge::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 26;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJudges::route('/'),
            'create' => Pages\CreateJudge::route('/create'),
            'view' => Pages\ViewJudge::route('/{record}'),
            'edit' => Pages\EditJudge::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Judge') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Judge') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return auth()->user()?->can('update Judge');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Judge');
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

        // Check if user has "archive Judge" permission
        return $user->can('archive Judge');
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

        // Check if user has "restore Judge" permission
        return $user->can('restore Judge');
    }
}
