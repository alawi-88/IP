<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StartupResource\Pages;
use App\Models\Startup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class StartupResource extends Resource
{
    protected static ?string $model = Startup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationGroup = 'Venture Analysis';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Startup Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(500),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(5000),
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('startup_logos'),
                    ])->columns(2),

                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('sector')
                            ->label('Sector')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('stage')
                            ->label('Stage')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('founding_date')
                            ->label('Founding Date'),
                        Forms\Components\TextInput::make('team_size')
                            ->label('Team Size')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'archived' => 'Archived',
                            ])
                            ->default('draft'),
                        Forms\Components\TextInput::make('completion_percentage')
                            ->label('Completion (%)')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Additional')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Owner')
                            ->relationship('user', 'name')
                            ->required()
                            ->disabled()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sector')
                    ->label('Sector')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stage')
                    ->label('Stage')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'archived' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Completion %')
                    ->numeric(decimals: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('team_size')
                    ->label('Team Size')
                    ->numeric(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ]),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Owner'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStartups::route('/'),
            'view' => Pages\ViewStartup::route('/{record}'),
            'edit' => Pages\EditStartup::route('/{record}/edit'),
        ];
    }
}
