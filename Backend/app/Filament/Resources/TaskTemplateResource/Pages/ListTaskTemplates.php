<?php

namespace App\Filament\Resources\TaskTemplateResource\Pages;

use App\Filament\Resources\TaskTemplateResource;
use App\Models\Program;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ListTaskTemplates extends ListRecords
{
    protected static string $resource = TaskTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('program.title')
                    ->label('Program')
                    ->getStateUsing(fn ($record) => $record->program?->getTranslation('title', 'en') ?? 'N/A')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('program', function ($q) use ($search) {
                            $q->where('title->en', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('title->en', 'like', "%{$search}%")
                              ->orWhere('title->ar', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_hours')
                    ->label('Est. Hours')
                    ->suffix('h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('form.title')
                    ->label('Form')
                    ->getStateUsing(fn ($record) => $record->form ? $record->form->getTranslation('title', 'en') : 'None'),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('Assignments')
                    ->getStateUsing(fn ($record) => $record->assignments()->count()),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label('Archived')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->options(fn () => Program::pluck('title', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t))
                    ->searchable(),

                SelectFilter::make('is_archived')
                    ->options([
                        '0' => 'Active',
                        '1' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
