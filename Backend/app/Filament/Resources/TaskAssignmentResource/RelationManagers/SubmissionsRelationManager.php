<?php

namespace App\Filament\Resources\TaskAssignmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Submissions / التسليمات';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'submitted',
                        'success' => 'approved',
                        'danger' => fn ($state) => in_array($state, ['rejected', 'revision_requested']),
                    ]),

                Tables\Columns\TextColumn::make('submittedByParticipant.name')
                    ->label('Submitted By'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin_feedback')
                    ->label('Feedback')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('reviewedByUser.name')
                    ->label('Reviewed By'),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime(),
            ])
            ->defaultSort('version', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Placeholder::make('version')
                            ->content(fn ($record) => "Version {$record->version}"),
                        Forms\Components\Placeholder::make('status')
                            ->content(fn ($record) => ucwords(str_replace('_', ' ', $record->status))),
                        Forms\Components\Placeholder::make('notes')
                            ->content(fn ($record) => $record->notes ?? 'No notes'),
                        Forms\Components\Placeholder::make('admin_feedback')
                            ->content(fn ($record) => $record->admin_feedback ?? 'No feedback yet'),
                        Forms\Components\Placeholder::make('files')
                            ->content(function ($record) {
                                $files = $record->files;
                                if (empty($files)) return 'No files attached';
                                return collect($files)->map(fn ($f) => is_string($f) ? $f : ($f['name'] ?? 'file'))->join(', ');
                            }),
                    ]),
            ]);
    }
}
