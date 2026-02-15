<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JudgeContactUsResource\Pages;
use App\Models\ContactUs;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class JudgeContactUsResource extends Resource
{
    protected static ?string $model = ContactUs::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Judges';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationGroup = 'Contact Us';


    public static function getBreadcrumb(): string
    {
        return 'Contact Us';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactUs::route('/'),
            'view' => Pages\ViewContactUs::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Contact Us records cannot be edited - they can only be deleted, archived, or restored
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view ContactUs') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete ContactUs');
    }

    public static function canArchive(Model $record): bool
    {
        return auth()->user()?->can('archive ContactUs') && !$record->isArchived();
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore ContactUs') && $record->isArchived();
    }
}
