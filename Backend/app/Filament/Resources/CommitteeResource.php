<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommitteeResource\Pages;
use App\Models\Committee;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class CommitteeResource extends Resource
{
    protected static ?string $model = Committee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Forms & Content';

    protected static ?int $navigationSort = 3;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommittees::route('/'),
            'create' => Pages\CreateCommittee::route('/create'),
            'view' => Pages\ViewCommittee::route('/{record}'),
            'edit' => Pages\EditCommittee::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Committee') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Committee') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Committee');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Committee');
    }
}
