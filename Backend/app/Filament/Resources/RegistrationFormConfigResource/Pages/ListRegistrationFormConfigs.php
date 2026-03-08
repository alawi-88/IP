<?php

namespace App\Filament\Resources\RegistrationFormConfigResource\Pages;

use App\Filament\Resources\RegistrationFormConfigResource;
use App\Models\RegistrationFormConfig;
use App\Models\UserProgram;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrationFormConfigs extends ListRecords
{
    protected static string $resource = RegistrationFormConfigResource::class;

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
            ->columns(RegistrationFormConfig::table())
            ->actions([
                Tables\Actions\Action::make('archive')
                    ->label(__('registration_config_archive.archive'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('registration_config_archive.confirm_archive'))
                    ->modalDescription(__('registration_config_archive.archive_confirmation'))
                    ->modalSubmitActionLabel(__('registration_config_archive.confirm_archive'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('registration_config_archive.archived_successfully'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && RegistrationFormConfigResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('registration_config_archive.restore'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('registration_config_archive.confirm_restore'))
                    ->modalDescription(__('registration_config_archive.restore_confirmation'))
                    ->modalSubmitActionLabel(__('registration_config_archive.confirm_restore'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('registration_config_archive.restored_successfully'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && RegistrationFormConfigResource::canRestore($record)),

                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
            ], position: ActionsPosition::AfterColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('registration_config_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('registration_config_archive.confirm_archive'))
                        ->modalDescription(__('registration_config_archive.archive_selected_confirmation'))
                        ->modalSubmitActionLabel(__('registration_config_archive.confirm_archive'))
                        ->action(function ($records) {
                            try {
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
                                        ->title(__('registration_config_archive.archived_successfully'))
                                        ->body(__('registration_config_archive.successfully_archived_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }

                                if ($alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('registration_config_archive.warning'))
                                        ->body(__('registration_config_archive.already_archived_count', ['count' => $alreadyArchived]))
                                        ->warning()
                                        ->send();
                                }

                                if ($count === 0 && $alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('registration_config_archive.no_action_taken'))
                                        ->body(__('registration_config_archive.all_selected_already_archived'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('registration_config_archive.failed_to_archive_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive RegistrationFormConfig') ?? false)
                        ->authorize(fn () => auth()->user()?->can('archive RegistrationFormConfig') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('registration_config_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('registration_config_archive.confirm_restore'))
                        ->modalDescription(__('registration_config_archive.restore_selected_confirmation'))
                        ->modalSubmitActionLabel(__('registration_config_archive.confirm_restore'))
                        ->action(function ($records) {
                            try {
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
                                        ->title(__('registration_config_archive.restored_successfully'))
                                        ->body(__('registration_config_archive.successfully_restored_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }

                                if ($alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('registration_config_archive.warning'))
                                        ->body(__('registration_config_archive.already_active_count', ['count' => $alreadyActive]))
                                        ->warning()
                                        ->send();
                                }

                                if ($count === 0 && $alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('registration_config_archive.no_action_taken'))
                                        ->body(__('registration_config_archive.all_selected_already_active'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('registration_config_archive.failed_to_restore_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore RegistrationFormConfig') ?? false)
                        ->authorize(fn () => auth()->user()?->can('restore RegistrationFormConfig') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete RegistrationFormConfig')),
                ]),
            ]);
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $baseQuery = RegistrationFormConfig::query();
        } else {
            $supervisorPrograms = \App\Models\UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();
            $baseQuery = RegistrationFormConfig::query()->whereIn('program_id', $supervisorPrograms);
        }

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => Tab::make(__('registration_config_archive.active'))
                ->badge((clone $baseQuery)->where('is_archived', false)->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', false)),
        ];

        // Add archived tab if user has archive/restore permissions
        if (auth()->user()?->can('archive RegistrationFormConfig') || auth()->user()?->can('restore RegistrationFormConfig')) {
            $tabs['archived'] = Tab::make(__('registration_config_archive.archived'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', true));
        }

        return $tabs;
    }
}
