<?php

namespace App\Filament\Resources\TeamFormConfigResource\Pages;

use App\Filament\Resources\TeamFormConfigResource;
use App\Models\TeamFormConfig;
use App\Models\UserProgram;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListTeamFormConfigs extends ListRecords
{
    protected static string $resource = TeamFormConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
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
                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                    ->pluck('program_id')
                    ->toArray();

                return $query->whereIn('program_id', $supervisorPrograms);
            })
            ->columns([
                Tables\Columns\TextColumn::make('program.title')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('min_team_members')
                    ->label('Min Members')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_team_members')
                    ->label('Max Members')
                    ->sortable(),

                Tables\Columns\IconColumn::make('allow_track_selection')
                    ->label('Track Selection')
                    ->boolean(),

                Tables\Columns\IconColumn::make('require_same_track')
                    ->label('Same Track Required')
                    ->boolean(),

                Tables\Columns\IconColumn::make('auto_publish_teams')
                    ->label('Auto Publish')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (TeamFormConfig $record) => !$record->isArchived()),

                Tables\Actions\Action::make('archive')
                    ->label(__('team_form_config_archive.archive_config'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('team_form_config_archive.archive_modal_heading'))
                    ->modalDescription(__('team_form_config_archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('team_form_config_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('team_form_config_archive.config_archived'))
                            ->body(__('team_form_config_archive.successfully_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && TeamFormConfigResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('team_form_config_archive.restore_config'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('team_form_config_archive.restore_modal_heading'))
                    ->modalDescription(__('team_form_config_archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('team_form_config_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('team_form_config_archive.config_restored'))
                            ->body(__('team_form_config_archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && TeamFormConfigResource::canRestore($record)),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('team_form_config_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('team_form_config_archive.archive_bulk_heading'))
                        ->modalDescription(__('team_form_config_archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('team_form_config_archive.archive_bulk_confirm'))
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
                                    ->title(__('team_form_config_archive.configs_archived'))
                                    ->body(__('team_form_config_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_form_config_archive.warning'))
                                    ->body(__('team_form_config_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_form_config_archive.no_action_taken'))
                                    ->body(__('team_form_config_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive TeamFormConfig'))
                        ->authorize(fn () => auth()->user()?->can('archive TeamFormConfig'))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('team_form_config_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('team_form_config_archive.restore_bulk_heading'))
                        ->modalDescription(__('team_form_config_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('team_form_config_archive.restore_bulk_confirm'))
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
                                    ->title(__('team_form_config_archive.configs_restored'))
                                    ->body(__('team_form_config_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_form_config_archive.warning'))
                                    ->body(__('team_form_config_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('team_form_config_archive.no_action_taken'))
                                    ->body(__('team_form_config_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore TeamFormConfig'))
                        ->authorize(fn () => auth()->user()?->can('restore TeamFormConfig'))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function getTabs(): array
    {
        $baseQuery = $this->getFilteredQuery();

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => Tab::make(__('team_form_config_archive.active_configs'))
                ->badge((clone $baseQuery)->notArchived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->notArchived()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive TeamFormConfig') || auth()->user()?->can('restore TeamFormConfig')) {
            $tabs['archived'] = Tab::make(__('team_form_config_archive.archived_configs'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }

    private function getFilteredQuery()
    {
        $query = TeamFormConfig::query();
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $supervisorPrograms = \App\Models\UserProgram::where('user_id', $user->id)
            ->pluck('program_id')
            ->toArray();

        return $query->whereIn('program_id', $supervisorPrograms);
    }
}
