<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationFormConfigResource\Pages;
use App\Models\RegistrationFormConfig;
use App\Models\UserCompetition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RegistrationFormConfigResource extends Resource
{
    protected static ?string $model = RegistrationFormConfig::class;

    // Managed via Competition Hub
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Forms & Configuration';

    protected static ?string $navigationLabel = 'Registration';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema(RegistrationFormConfig::form());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    return $query;
                }
                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                    ->pluck('competition_id')
                    ->toArray();

                return $query->whereIn('competition_id', $supervisorCompetitions);
            })
            ->columns(RegistrationFormConfig::table())
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete RegistrationFormConfig')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrationFormConfigs::route('/'),
            'create' => Pages\CreateRegistrationFormConfig::route('/create'),
            'edit' => Pages\EditRegistrationFormConfig::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view RegistrationFormConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create RegistrationFormConfig') ?? false;
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canEdit(Model $record): bool
    {
        if ($record->isArchived() || !auth()->user()?->can('update RegistrationFormConfig')) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canDelete(Model $record): bool
    {
        if (!auth()->user()?->can('delete RegistrationFormConfig')) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canArchive(Model $record): bool
    {
        if (!auth()->user()?->can('archive RegistrationFormConfig') || $record->isArchived()) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }

    /**
     * IDOR prevention: verify user has access to the competition.
     */
    public static function canRestore(Model $record): bool
    {
        if (!auth()->user()?->can('restore RegistrationFormConfig') || !$record->isArchived()) {
            return false;
        }
        return $record->competition && $record->competition->canAccessProgram();
    }
}
