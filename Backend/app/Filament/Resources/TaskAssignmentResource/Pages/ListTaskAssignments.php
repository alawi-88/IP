<?php

namespace App\Filament\Resources\TaskAssignmentResource\Pages;

use App\Filament\Resources\TaskAssignmentResource;
use App\Models\Program;
use App\Models\TaskAssignment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ListTaskAssignments extends ListRecords
{
    protected static string $resource = TaskAssignmentResource::class;

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
                    ->label('Task Title')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('title->en', 'like', "%{$search}%")
                              ->orWhere('title->ar', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('assignment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'team' => 'info',
                        'participant' => 'warning',
                        'all' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('assignee_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn ($record) => $record->assignee_name),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => TaskAssignment::STATUS_NOT_STARTED,
                        'info' => TaskAssignment::STATUS_IN_PROGRESS,
                        'warning' => TaskAssignment::STATUS_SUBMITTED,
                        'danger' => TaskAssignment::STATUS_REVISION_REQUESTED,
                        'success' => TaskAssignment::STATUS_APPROVED,
                        'danger' => TaskAssignment::STATUS_REJECTED,
                    ]),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && !in_array($record->status, ['approved', 'rejected']) ? 'danger' : null),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->getStateUsing(fn ($record) => $record->submissions()->count()),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->options(fn () => Program::pluck('title', 'id')->map(fn ($t) => is_array($t) ? ($t['en'] ?? '') : $t))
                    ->searchable(),

                SelectFilter::make('status')
                    ->options(array_combine(TaskAssignment::STATUSES, array_map(fn ($s) => ucwords(str_replace('_', ' ', $s)), TaskAssignment::STATUSES))),

                SelectFilter::make('assignment_type')
                    ->options([
                        'team' => 'Team',
                        'participant' => 'Individual',
                        'all' => 'All Participants',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
