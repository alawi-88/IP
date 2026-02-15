<?php

namespace App\Filament\Resources\ProjectFormConfigResource\Pages;

use App\Filament\Resources\ProjectFormConfigResource;
use App\Models\ProjectFormConfig;
use App\Models\UserCompetition;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListProjectFormConfigs extends ListRecords
{
    protected static string $resource = ProjectFormConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('project_form_config_archive.all'))
                ->badge(ProjectFormConfig::query()->count())
                ->modifyQueryUsing(function (Builder $query) {
                    $user = auth()->user();

                    if ($user->isSuperAdmin()) {
                        return $query;
                    }

                    $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                        ->pluck('competition_id')
                        ->toArray();

                    return $query->whereHas('form', function ($q) use ($supervisorCompetitions) {
                        $q->whereIn('competition_id', $supervisorCompetitions);
                    });
                }),

            'active' => Tab::make(__('project_form_config_archive.active'))
                ->badge(ProjectFormConfig::query()->where('is_archived', false)->count())
                ->modifyQueryUsing(function (Builder $query) {
                    $user = auth()->user();

                    if ($user->isSuperAdmin()) {
                        return $query->where('is_archived', false);
                    }

                    $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                        ->pluck('competition_id')
                        ->toArray();

                    return $query->where('is_archived', false)
                        ->whereHas('form', function ($q) use ($supervisorCompetitions) {
                            $q->whereIn('competition_id', $supervisorCompetitions);
                        });
                }),

            'archived' => Tab::make(__('project_form_config_archive.archived'))
                ->badge(ProjectFormConfig::query()->where('is_archived', true)->count())
                ->modifyQueryUsing(function (Builder $query) {
                    $user = auth()->user();

                    if ($user->isSuperAdmin()) {
                        return $query->where('is_archived', true);
                    }

                    $supervisorCompetitions = UserCompetition::where('user_id', $user->id)
                        ->pluck('competition_id')
                        ->toArray();

                    return $query->where('is_archived', true)
                        ->whereHas('form', function ($q) use ($supervisorCompetitions) {
                            $q->whereIn('competition_id', $supervisorCompetitions);
                        });
                }),
        ];
    }

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

                return $query->whereHas('form', function ($q) use ($supervisorCompetitions) {
                    $q->whereIn('competition_id', $supervisorCompetitions);
                });
            })
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('form.name')
                    ->label('Form')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\IconColumn::make('allow_track_change')
                    ->label('Allow Track Change')
                    ->boolean()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->visible(fn (ProjectFormConfig $record) => !$record->isArchived()),

                Action::make('archive')
                    ->label(__('project_form_config_archive.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('project_form_config_archive.confirm_archive'))
                    ->modalDescription(__('project_form_config_archive.archive_warning'))
                    ->action(function (ProjectFormConfig $record) {
                        if ($record->archive()) {
                            Notification::make()
                                ->title(__('project_form_config_archive.archive_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('project_form_config_archive.archive_error'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (ProjectFormConfig $record) => static::getResource()::canArchive($record)),

                Action::make('restore')
                    ->label(__('project_form_config_archive.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('project_form_config_archive.confirm_restore'))
                    ->modalDescription(__('project_form_config_archive.restore_warning'))
                    ->action(function (ProjectFormConfig $record) {
                        if ($record->restore()) {
                            Notification::make()
                                ->title(__('project_form_config_archive.restore_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('project_form_config_archive.restore_error'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (ProjectFormConfig $record) => static::getResource()::canRestore($record)),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete ProjectFormConfig')),

                    BulkAction::make('archive')
                        ->label(__('project_form_config_archive.archive'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('project_form_config_archive.confirm_bulk_archive'))
                        ->modalDescription(__('project_form_config_archive.bulk_archive_warning'))
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyArchived = 0;

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    $alreadyArchived++;
                                } else {
                                    if ($record->archive()) {
                                        $count++;
                                    }
                                }
                            }

                            if ($count > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.bulk_archive_success', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyArchived > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.warning'))
                                    ->body(__('project_form_config_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.no_action_taken'))
                                    ->body(__('project_form_config_archive.all_selected_already_archived'))
                                    ->info()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->can('archive ProjectFormConfig')),

                    BulkAction::make('restore')
                        ->label(__('project_form_config_archive.restore'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('project_form_config_archive.confirm_bulk_restore'))
                        ->modalDescription(__('project_form_config_archive.bulk_restore_warning'))
                        ->action(function (Collection $records) {
                            $count = 0;
                            $alreadyActive = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $alreadyActive++;
                                } else {
                                    if ($record->restore()) {
                                        $count++;
                                    }
                                }
                            }

                            if ($count > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.bulk_restore_success', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.warning'))
                                    ->body(__('project_form_config_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyActive > 0) {
                                Notification::make()
                                    ->title(__('project_form_config_archive.no_action_taken'))
                                    ->body(__('project_form_config_archive.all_selected_already_active'))
                                    ->info()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->can('restore ProjectFormConfig')),
                ]),
            ]);
    }
}
