<?php

namespace App\Filament\Resources;

use Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource as BasePermissionResource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PermissionResource extends BasePermissionResource
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?string $navigationLabel = 'Permissions';
    protected static ?int $navigationSort = 2;

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
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->rule('regex:/^[A-Za-z\s]+$/')
                        ->validationMessages([
                            'unique' => 'The role name is already in use, please choose another name.',
                            'regex' => 'The permission name may only contain letters and spaces.',
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

                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->required()
                    ->validationMessages([
                        'required' => 'Please select at least one Role.',
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Permission Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'view') => 'info',
                        str_contains($state, 'create') => 'success',
                        str_contains($state, 'update') => 'warning',
                        str_contains($state, 'delete') => 'danger',
                        str_contains($state, 'archive') => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
                SelectFilter::make('permission_type')
                    ->label('Permission Type')
                    ->options([
                        'view' => 'View',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'archive' => 'Archive',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->where('name', 'like', '%' . $data['value'] . '%');
                    }),
            ])
            ->defaultSort('name')
            ->actions([
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => PermissionResource\Pages\ListPermissions::route('/'),
            'create' => PermissionResource\Pages\CreatePermission::route('/create'),
            'edit' => PermissionResource\Pages\EditPermission::route('/{record}/edit'),
            'view' => PermissionResource\Pages\ViewPermission::route('/{record}'),
        ];
    }
}

