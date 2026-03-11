<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenturePromptTemplateResource\Pages;
use App\Models\VenturePromptTemplate;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VenturePromptTemplateResource extends Resource
{
    protected static ?string $model = VenturePromptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 4;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenturePromptTemplates::route('/'),
            'create' => Pages\CreateVenturePromptTemplate::route('/create'),
            'edit' => Pages\EditVenturePromptTemplate::route('/{record}/edit'),
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
