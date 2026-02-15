<?php

namespace App\Filament\Resources;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource as BaseRoleResource;
use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers\PermissionsRelationManager;
use App\Filament\Resources\RoleResource\RelationManagers\UsersRelationManager;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Forms\Form;
use Filament\Tables\Columns\BadgeColumn;
class RoleResource extends BaseRoleResource
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?string $navigationLabel = 'Roles';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()->schema([
                Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->rule('regex:/^[A-Za-z\s]+$/')
                    ->validationMessages([
                        'unique' => 'The role name is already in use, please choose another name.',
                        'regex' => 'The role name may only contain letters and spaces.',
                    ]),

               Select::make('guard_name')
                   ->label('Guard Name')
                   ->default('web')
                   ->options([
                       'web' => 'web',
                       'api' => 'api',
                   ])
                   ->required()
                   ->native(false),
            ]),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Enter details about this role...')
                    ->required(false),

            Select::make('permissions')
                ->label('Permissions')
                ->multiple()
                ->relationship('permissions', 'name')
                ->preload()
                ->searchable()
                ->columnSpanFull()
                ->required()
                ->validationMessages([
                    'required' => 'Please select at least one permission.',
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('name', '!=', 'super-admin'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super-admin' => 'danger',
                        'admin' => 'warning',
                        'supervisor' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            PermissionsRelationManager::class,
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
        ];
    }

}

