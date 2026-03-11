<?php

namespace App\Filament\Resources\VentureResource\Pages;

use App\Filament\Resources\VentureResource;
use App\Models\Venture;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ListVentures extends ListRecords
{
    protected static string $resource = VentureResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(Venture::byCompetition())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('participant.name')
                    ->label('Participant')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'generating',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('viability_score')
                    ->suffix('/100')
                    ->sortable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_archived')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'generating' => 'Generating',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\TernaryFilter::make('is_archived'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
