<?php

namespace App\Filament\Resources\JudgeResource\Pages;

use App\Filament\Resources\JudgeResource;
use App\Models\Judge;
use App\Models\UserCompetition;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables\Table;
use Filament\Tables;
use App\Filament\Exports\JudgeExporter;
use Filament\Tables\Actions\ExportBulkAction;
use App\Models\Competition;
use Filament\Forms;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ListJudges extends ListRecords
{
    protected static string $resource = JudgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


    // public function table(Table $table): Table
    // {
    //     return $table
    //         ->modifyQueryUsing(function ($query) {
    //             $user = auth()->user();
    //             if ($user->isSuperAdmin()) {
    //                 return $query;
    //             }
    //             $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
    //                 ->pluck('competition_id')
    //                 ->toArray();

    //             return $query->whereHas('competitions', function ($q) use ($supervisorCompetitions) {
    //                 $q->whereIn('competitions.id', $supervisorCompetitions);
    //             });
    //         })
    //         ->columns(array_merge(Judge::columns(), [
    //             Tables\Columns\TextColumn::make('created_at')
    //                 ->searchable()
    //                 ->sortable()
    //         ]))
    //         ->actions([
    //             Tables\Actions\ViewAction::make(),
    //             Tables\Actions\EditAction::make(),
    //             Tables\Actions\DeleteAction::make(),
    //             // Add new action for single record competition assignment
    //             // Tables\Actions\Action::make('assign_competitions')
    //             //     ->label('Competitions')
    //             //     ->icon('heroicon-o-academic-cap')
    //             //     ->visible(fn () => auth()->user()->can('update Judge'))
    //             //     ->form([
    //             //         Forms\Components\Select::make('competitions')
    //             //             ->label('Competitions')
    //             //             ->multiple()
    //             //             ->relationship('competitions', 'title')
    //             //             ->options(function () {
    //             //                 $user = auth()->user();

    //             //                 if ($user->isSuperAdmin()) {
    //             //                     return Competition::pluck('title', 'id');
    //             //                 }

    //             //                 $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
    //             //                     ->pluck('competition_id');

    //             //                 return Competition::whereIn('id', $supervisorCompetitions)
    //             //                     ->pluck('title', 'id');
    //             //             })
    //             //             ->preload()
    //             //             ->required()
    //             //             ->default(fn (Judge $record): array =>
    //             //             $record->competitions()->pluck('competitions.id')->toArray()
    //             //             )
    //             //     ])
    //             Tables\Actions\Action::make('assign_competitions')
    //             ->label('Competitions')
    //             ->icon('heroicon-o-academic-cap')
    //             ->visible(fn () => auth()->user()->can('update Judge'))
    //             ->form([
    //                 Forms\Components\Select::make('competitions')
    //                     ->label('Competitions')
    //                     ->multiple()
    //                     ->options(function () {
    //                         $user = auth()->user();
            
    //                         if ($user->isSuperAdmin()) {
    //                             return Competition::pluck('title', 'id');
    //                         }
            
    //                         $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
    //                             ->pluck('competition_id');
            
    //                         return Competition::whereIn('id', $supervisorCompetitions)
    //                             ->pluck('title', 'id');
    //                     })
    //                     ->preload()
    //                     ->required()
    //                     ->default(fn (Judge $record): array =>
    //                         $record->competitions()->pluck('competitions.id')->toArray()
    //                     ),
    //             ])
    //                 ->after(
    //                     fn() =>
    //                     Notification::make()
    //                         ->success()
    //                         ->title('Competitions updated')
    //                         ->send()
    //                 ),

    //         ])
    //         ->filters([
    //             Tables\Filters\SelectFilter::make('competition')
    //                 ->relationship('competitions', 'title')
    //                 ->multiple()
    //                 ->preload()
    //                 ->label('Competition'),

    //             Tables\Filters\SelectFilter::make('registration_method')
    //                 ->options(Judge::getRegistrationMethods())
    //                 ->label('Registration Method'),
    //         ])
    //         ->defaultSort('created_at', 'desc')
    //         ->bulkActions([
    //             Tables\Actions\BulkActionGroup::make([
    //                 Tables\Actions\DeleteBulkAction::make()
    //                     ->visible(fn () => auth()->user()->can('delete Judge')),
    //                 Tables\Actions\BulkAction::make('assign_competitions')
    //                     ->label('Assign Competitions')
    //                     ->icon('heroicon-o-academic-cap')
    //                     ->visible(fn () => auth()->user()->can('update Judge'))
    //                     ->form([
    //                         Forms\Components\Select::make('competitions')
    //                             ->label('Competitions')
    //                             ->multiple()
    //                             ->relationship('competitions', 'title', fn ($query) =>
    //                             auth()->user()->isSuperAdmin()
    //                                 ? $query
    //                                 : $query->whereIn('competitions.id', auth()->user()->competitions()->pluck('competitions.id'))
    //                             )
    //                             ->preload()
    //                             ->required(),
    //                     ])

    //                     ->action(function (Collection $records, array $data): void {
    //                         foreach ($records as $record) {
    //                             $record->competitions()->sync($data['competitions'] ?? []);
    //                         }
    //                     })
    //                     ->deselectRecordsAfterCompletion()
    //                     ->successNotification(
    //                         Notification::make()
    //                             ->success()
    //                             ->title('Competitions assigned')
    //                             ->body('The selected judges have been assigned to the competitions successfully.')
    //                     ),
    //             ]),
    //             ExportBulkAction::make()
    //                 ->columnMapping(false)
    //                 ->exporter(JudgeExporter::class)
    //                 ->fileName('Judges_List_' . now()->format('Y-m-d')),
    //         ]);
    // }
    public function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(function ($query) {
            $user = auth()->user();

            if ($user->isSuperAdmin()) {
                return $query;
            }

            $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                ->pluck('competition_id')
                ->toArray();

            return $query->whereHas('competitions', function ($q) use ($supervisorCompetitions) {
                $q->whereIn('competitions.id', $supervisorCompetitions);
            });
        })
        ->columns(array_merge(Judge::columns(), [
            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\IconColumn::make('is_archived')
                ->label(__('judge_archive.archived_judges'))
                ->boolean()
                ->searchable()
                ->visible(fn() => auth()->user()?->can('archive Judge') || auth()->user()?->can('restore Judge')),
        ]))
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make()
                ->visible(fn ($record) => !$record->isArchived()),
            Tables\Actions\DeleteAction::make(),

            Tables\Actions\Action::make('archive')
                ->label(__('judge_archive.archive_judge'))
                ->color('warning')
                ->icon('heroicon-o-archive-box')
                ->requiresConfirmation()
                ->modalHeading(__('judge_archive.archive_modal_heading'))
                ->modalDescription(__('judge_archive.archive_modal_description'))
                ->modalSubmitActionLabel(__('judge_archive.archive_modal_confirm'))
                ->action(function ($record) {
                    $record->archive();
                    \Filament\Notifications\Notification::make()
                        ->title(__('judge_archive.judge_archived'))
                        ->body(__('judge_archive.successfully_archived'))
                        ->success()
                        ->send();
                })
                ->visible(fn($record) => !$record->isArchived() && JudgeResource::canArchive($record)),

            Tables\Actions\Action::make('restore')
                ->label(__('judge_archive.restore_judge'))
                ->color('success')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->modalHeading(__('judge_archive.restore_modal_heading'))
                ->modalDescription(__('judge_archive.restore_modal_description'))
                ->modalSubmitActionLabel(__('judge_archive.restore_modal_confirm'))
                ->action(function ($record) {
                    $record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title(__('judge_archive.judge_restored'))
                        ->body(__('judge_archive.successfully_restored'))
                        ->success()
                        ->send();
                })
                ->visible(fn($record) => $record->isArchived() && JudgeResource::canRestore($record)),

            Tables\Actions\Action::make('assign_competitions')
                ->label('Programs')
                ->icon('heroicon-o-academic-cap')
                ->visible(fn ($record) => !$record->isArchived() && auth()->user()->can('update Judge'))
                ->form([
                    Forms\Components\Select::make('competitions')
                        ->label('Programs')
                        ->multiple()
                        ->options(function () {
                            $user = auth()->user();

                            if ($user->isSuperAdmin()) {
                                return Competition::pluck('title', 'id');
                            }

                            $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                                ->pluck('competition_id');

                            return Competition::whereIn('id', $supervisorCompetitions)
                                ->pluck('title', 'id');
                        })
                        ->preload()
                        ->required()
                        ->default(fn (Judge $record): array =>
                            $record->competitions()->pluck('competitions.id')->toArray()
                        ),
                ])
                ->action(function (Judge $record, array $data) {
                    $record->competitions()->sync($data['competitions'] ?? []);
                    Notification::make()
                        ->success()
                        ->title('Programs updated')
                        ->send();
                }),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('competition')
                ->relationship('competitions', 'title')
                ->multiple()
                ->preload()
                ->label('Program'),

            Tables\Filters\SelectFilter::make('registration_method')
                ->options(Judge::getRegistrationMethods())
                ->label('Registration Method'),
        ])
        ->defaultSort('created_at', 'desc')
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete Judge')),

                Tables\Actions\BulkAction::make('archive')
                    ->label(__('judge_archive.archive_selected'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('judge_archive.archive_bulk_heading'))
                    ->modalDescription(__('judge_archive.archive_bulk_description'))
                    ->modalSubmitActionLabel(__('judge_archive.archive_bulk_confirm'))
                    ->action(function ($records) {
                        $count = 0;
                        $alreadyArchived = 0;
                        
                        foreach ($records as $record) {
                            if (!$record->isArchived()) {
                                $record->archive();
                                $count++;
                            } else {
                                $alreadyArchived++;
                            }
                        }
                        
                        if ($count > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.judges_archived'))
                                ->body(__('judge_archive.successfully_archived_count', ['count' => $count]))
                                ->success()
                                ->send();
                        }
                        
                        if ($alreadyArchived > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.warning'))
                                ->body(__('judge_archive.already_archived_count', ['count' => $alreadyArchived]))
                                ->warning()
                                ->send();
                        }
                        
                        if ($count === 0 && $alreadyArchived > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.no_action_taken'))
                                ->body(__('judge_archive.all_selected_already_archived'))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn () => auth()->user()?->can('archive Judge'))
                    ->authorize(fn () => auth()->user()?->can('archive Judge'))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('restore')
                    ->label(__('judge_archive.restore_selected'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('judge_archive.restore_bulk_heading'))
                    ->modalDescription(__('judge_archive.restore_bulk_description'))
                    ->modalSubmitActionLabel(__('judge_archive.restore_bulk_confirm'))
                    ->action(function ($records) {
                        $count = 0;
                        $alreadyActive = 0;
                        
                        foreach ($records as $record) {
                            if ($record->isArchived()) {
                                $record->restore();
                                $count++;
                            } else {
                                $alreadyActive++;
                            }
                        }
                        
                        if ($count > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.judges_restored'))
                                ->body(__('judge_archive.successfully_restored_count', ['count' => $count]))
                                ->success()
                                ->send();
                        }
                        
                        if ($alreadyActive > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.warning'))
                                ->body(__('judge_archive.already_active_count', ['count' => $alreadyActive]))
                                ->warning()
                                ->send();
                        }
                        
                        if ($count === 0 && $alreadyActive > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('judge_archive.no_action_taken'))
                                ->body(__('judge_archive.all_selected_already_active'))
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn () => auth()->user()?->can('restore Judge'))
                    ->authorize(fn () => auth()->user()?->can('restore Judge'))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('assign_competitions')
                    ->label('Assign Programs')
                    ->icon('heroicon-o-academic-cap')
                    ->visible(fn () => auth()->user()->can('update Judge'))
                    ->form([
                        Forms\Components\Select::make('competitions')
                            ->label('Programs')
                            ->multiple()
                            ->options(function () {
                                $user = auth()->user();

                                if ($user->isSuperAdmin()) {
                                    return Competition::pluck('title', 'id');
                                }

                                $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                                    ->pluck('competition_id');

                                return Competition::whereIn('id', $supervisorCompetitions)
                                    ->pluck('title', 'id');
                            })
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $count = 0;
                        $skippedArchived = 0;
                        
                        foreach ($records as $record) {
                            // Skip archived records - they cannot be updated
                            if ($record->isArchived()) {
                                $skippedArchived++;
                                continue;
                            }
                            $record->competitions()->sync($data['competitions'] ?? []);
                            $count++;
                        }
                        
                        if ($count > 0) {
                            Notification::make()
                                ->success()
                                ->title('Programs assigned / تم تعيين البرامج')
                                ->body("{$count} judge(s) have been assigned to programs successfully. / تم تعيين {$count} حكم للبرامج بنجاح.")
                                ->send();
                        }
                        
                        if ($skippedArchived > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Archived Judges Skipped / تم تخطي الحكام المؤرشفين')
                                ->body("{$skippedArchived} archived judge(s) were skipped. Archived judges cannot be updated. / تم تخطي {$skippedArchived} حكم مؤرشف. لا يمكن تحديث الحكام المؤرشفين.")
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]),
            ExportBulkAction::make()
                ->columnMapping(false)
                ->exporter(JudgeExporter::class)
                ->fileName('Judges_List_' . now()->format('Y-m-d')),
        ]);
}

    public function getTabs(): array
    {
        $user = auth()->user();
        $baseQuery = Judge::query();

        // Apply competition filtering for non-super admins
        if (!$user->isSuperAdmin()) {
            $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                ->pluck('competition_id')
                ->toArray();

            $baseQuery = $baseQuery->whereHas('competitions', function ($q) use ($supervisorCompetitions) {
                $q->whereIn('competitions.id', $supervisorCompetitions);
            });
        }

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),
            
            'active' => Tab::make(__('judge_archive.active_judges'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive Judge') || auth()->user()?->can('restore Judge')) {
            $tabs['archived'] = Tab::make(__('judge_archive.archived_judges'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
