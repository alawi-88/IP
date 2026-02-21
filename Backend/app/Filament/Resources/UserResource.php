<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\ProgramsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\RolesRelationManager;
use App\Models\Program;
use App\Models\User;
use App\Models\UserProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Users & Roles';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('programs')
                    ->label('Programs')
                    ->multiple()
                    ->relationship('programs', 'title')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->isSuperAdmin()) {
                            return Program::pluck('title', 'id');
                        }

                        $supervisorPrograms = UserProgram::where('user_id', $user->id)
                            ->pluck('program_id');

                        return Program::whereIn('id', $supervisorPrograms)
                            ->pluck('title', 'id');
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->unique('users', 'email', ignoreRecord: true)
                    ->columnSpanFull(),

                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship(
                        'roles',
                        'name',
                        fn ($query) => $query->where('name', '!=', 'super-admin')
                    )

                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    return $query;
                }

                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                    ->pluck('program_id')
                    ->toArray();

                return $query->whereHas('programs', function ($q) use ($supervisorPrograms) {
                    $q->whereIn('programs.id', $supervisorPrograms);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('programs_count')
                    ->label('Programs Count')
                    ->counts('programs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i') : 'Never')
                    ->sortable(),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Admin')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProgramsRelationManager::class,
            RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getPluralLabel(): ?string
    {
        return 'Admins';   // Sidebar & index page
    }

    public static function getLabel(): ?string
    {
        return 'Admin';    // Single record label
    }
    public static function getNavigationLabel(): string
    {
        return 'Admins';
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Admin');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('view Admin');
    }

    public static function canEdit(Model $record): bool
    {
        // Archived records cannot be edited - only deleted or restored
        if ($record->isArchived()) {
            return false;
        }

        return auth()->user()?->can('update Admin');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Admin');
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

        // Check if user has "archive User" permission
        return $user->can('archive User');
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

        // Check if user has "restore User" permission
        return $user->can('restore User');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'));
    }

}
