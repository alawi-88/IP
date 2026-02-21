<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuidelineResource\Pages;
use App\Filament\Resources\GuidelineResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Livewire\GuidelineComponent;
use App\Models\Guideline;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class GuidelineResource extends Resource
{
    use CanBeDeletable;

    protected static ?string $model = Guideline::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Forms & Content';


    public static function getRelations(): array
    {
        return [
            RelationManagers\FilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuidelines::route('/'),
            'create' => Pages\CreateGuideline::route('/create'),
            'view' => Pages\ViewGuideline::route('/{record}'),
            'edit' => Pages\EditGuideline::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Guideline') ?? false;
    }

    public static function canCreate(): bool
    {
        if (! auth()->user()?->can('create Guideline')) {
            return false;
        }

        return ! empty(currentProgramId());
    }

    public static function canEdit(Model $record): bool
    {
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }
        
        return auth()->user()?->can('update Guideline');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Guideline');
    }

    public static function canArchive(Model $record): bool
    {
        return auth()->user()?->can('archive Guideline');
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('restore Guideline');
    }

}
