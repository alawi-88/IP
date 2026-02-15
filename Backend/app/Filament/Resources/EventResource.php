<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Traits\CanBeDeletable;
use App\Models\Event;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class EventResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationGroup = 'Programs';


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Event') ?? false;
    }

    public static function canCreate(): bool
    {
        if (! auth()->user()?->can('create Event')) {
            return false;
        }

        return ! empty(currentCompetitionId());
    }

    public static function canEdit(Model $record): bool
    {
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return auth()->user()?->can('update Event');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Event');
    }

    public static function canArchive(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Check if user has "archive Event" permission
        return $user->can('archive Event');
    }

    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Check if user has "restore Event" permission
        return $user->can('restore Event');
    }
}
