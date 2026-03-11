<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureKnowledgeSourceResource\Pages;
use App\Models\VentureKnowledgeSource;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureKnowledgeSourceResource extends Resource
{
    protected static ?string $model = VentureKnowledgeSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 5;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentureKnowledgeSources::route('/'),
            'create' => Pages\CreateVentureKnowledgeSource::route('/create'),
            'edit' => Pages\EditVentureKnowledgeSource::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }
}
