<?php

namespace App\Filament\Resources\MentorSessionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    protected static ?string $modelLabel = 'Activity';

    protected static ?string $pluralModelLabel = 'Activities';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label(__('sessions.activity_description'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label(__('sessions.activity_performed_by'))
                    ->formatStateUsing(fn ($state) => $state ?? __('sessions.system'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label(__('sessions.activity_event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sessions.activity_date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
              //
            ])
            ->bulkActions([
                //
            ]);
    }
}
