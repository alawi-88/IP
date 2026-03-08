<?php

namespace App\Filament\Resources\RegistrationEvaluationFormResource\Pages;

use App\Filament\Resources\RegistrationEvaluationFormResource;
use App\Models\Program;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ListRegistrationEvaluationForms extends ListRecords
{
    protected static string $resource = RegistrationEvaluationFormResource::class;

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
                            $q->where('title->en', 'like', "%{$search}%")
                              ->orWhere('title->ar', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Form Name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->en', 'like', "%{$search}%")
                              ->orWhere('name->ar', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('dimension')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scoring_scale')
                    ->label('Scale')
                    ->sortable(),

                Tables\Columns\TextColumn::make('criteria_count')
                    ->label('Criteria')
                    ->getStateUsing(fn ($record) => $record->criteria()->count())
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'gray' => 'archived',
                    ]),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->options(fn () => Program::pluck('title', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? $t['ar'] ?? '') : $t))
                    ->searchable(),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
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
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
