<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use App\Filament\Resources\CompetitionResource;

class CompetitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'competitions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Program Title')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                Actions\DeleteAction::make()
                    ->authorize(fn ($record) =>
                        auth()->user()?->isSuperAdmin()
                        || CompetitionResource::canDelete($record)
                    ),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(function ($records) {
                        // Show to super-admin even before records are selected
                        $user = auth()->user();
                        if (blank($records)) {
                            return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
                        }
                        return $records->every(fn ($record) => CompetitionResource::canDelete($record));
                    }),
            ]);
    }
}
