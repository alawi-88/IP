<?php

namespace App\Filament\Resources\RegistrationEvaluatorResource\Pages;

use App\Filament\Resources\RegistrationEvaluatorResource;
use App\Models\Competition;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ListRegistrationEvaluators extends ListRecords
{
    protected static string $resource = RegistrationEvaluatorResource::class;

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

                Tables\Columns\TextColumn::make('competition.title')
                    ->label('Program')
                    ->getStateUsing(fn ($record) => $record->competition?->getTranslation('title', 'en') ?? 'N/A')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('competition', function ($q) use ($search) {
                            $q->where('title->en', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Evaluator')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('assigned_sections_count')
                    ->label('Assigned Sections')
                    ->getStateUsing(fn ($record) => $record->assignedSections()->count()),

                Tables\Columns\TextColumn::make('evaluations_count')
                    ->label('Evaluations Done')
                    ->getStateUsing(fn ($record) => $record->evaluations()->count()),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('competition_id')
                    ->label('Program')
                    ->options(fn () => Competition::pluck('title', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t))
                    ->searchable(),

                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
