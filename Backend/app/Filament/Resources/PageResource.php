<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;


class PageResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 28;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Page') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Page');
    }

}
