<?php

namespace App\Filament\Resources\ParticipantResource\Pages;

use App\Filament\Exports\ParticipantExporter;
use App\Filament\Imports\ParticipantImporter;
use App\Filament\Resources\ParticipantResource;
use App\Models\Program;
use App\Models\Country;
use App\Models\Participant;
use Filament\Actions;
use Filament\Actions\Imports\Models\Import;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\File;
use Filament\Tables\Actions\BulkAction;

class ListParticipants extends ListRecords
{
    protected static string $resource = ParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(array_merge(Participant::columns(), [
                \Filament\Tables\Columns\IconColumn::make('is_archived')
                    ->label(__('participant_archive.archived_participants'))
                    ->boolean()
                    ->searchable()
                    ->visible(fn() => auth()->user()?->can('archive Participant') || auth()->user()?->can('restore Participant')),
            ]))
            ->headerActions([
                Action::make('import_progress')
                    ->label(function () {
                        $import = Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNull('completed_at')
                            ->latest()
                            ->first();
                        
                        if (!$import || $import->total_rows === 0) {
                            return 'Importing... 0%';
                        }
                        
                        $percentage = round(($import->processed_rows / $import->total_rows) * 100);
                        return "Importing... {$percentage}% ({$import->processed_rows}/{$import->total_rows})";
                    })
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->disabled()
                    ->extraAttributes([
                        'class' => 'import-rotate',
                    ])
                    ->visible(function () {
                        return Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNull('completed_at')
                            ->exists();
                    }),

                Action::make('import_completed')
                    ->label(function () {
                        $import = Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNotNull('completed_at')
                            ->where('completed_at', '>=', now()->subSeconds(5))
                            ->latest()
                            ->first();
                        
                        if (!$import) {
                            return 'Import Complete!';
                        }
                        
                        $failedCount = $import->getFailedRowsCount();
                        if ($failedCount > 0) {
                            return " Imported {$import->successful_rows} rows ({$failedCount} failed)";
                        }
                        
                        return " Import Complete! {$import->successful_rows} rows imported";
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->disabled()
                    ->extraAttributes([
                    ])
                    ->visible(function () {
                        return Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNotNull('completed_at')
                            ->where('completed_at', '>=', now()->subSeconds(5))
                            ->exists();
                    }),

                ImportAction::make()
                    ->importer(ParticipantImporter::class)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(function () {
                        if (!auth()->user()->can('create Participant')) {
                            return false;
                        }
                        $hasActiveImport = Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNull('completed_at')
                            ->exists();
                        
                        $hasCompletionMessage = Import::where('user_id', auth()->id())
                            ->where('importer', ParticipantImporter::class)
                            ->whereNotNull('completed_at')
                            ->where('completed_at', '>=', now()->subSeconds(5))
                            ->exists();
                        
                        return !$hasActiveImport && !$hasCompletionMessage;
                    }),
            ])
            ->poll(function () {
                $hasActiveOrRecent = Import::where('user_id', auth()->id())
                    ->where('importer', ParticipantImporter::class)
                    ->where(function ($query) {
                        $query->whereNull('completed_at')
                            ->orWhere('completed_at', '>=', now()->subSeconds(5));
                    })
                    ->exists();
                
                return $hasActiveOrRecent ? '3s' : null;
            })
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
                DeleteAction::make(),

                \Filament\Tables\Actions\Action::make('archive')
                    ->label(__('participant_archive.archive_participant'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('participant_archive.archive_modal_heading'))
                    ->modalDescription(__('participant_archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('participant_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('participant_archive.participant_archived'))
                            ->body(__('participant_archive.successfully_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && ParticipantResource::canArchive($record)),

                \Filament\Tables\Actions\Action::make('restore')
                    ->label(__('participant_archive.restore_participant'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('participant_archive.restore_modal_heading'))
                    ->modalDescription(__('participant_archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('participant_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('participant_archive.participant_restored'))
                            ->body(__('participant_archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && ParticipantResource::canRestore($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete Participant')),

                    BulkAction::make('archive')
                        ->label(__('participant_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('participant_archive.archive_bulk_heading'))
                        ->modalDescription(__('participant_archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('participant_archive.archive_bulk_confirm'))
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
                                    ->title(__('participant_archive.participants_archived'))
                                    ->body(__('participant_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('participant_archive.warning'))
                                    ->body(__('participant_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('participant_archive.no_action_taken'))
                                    ->body(__('participant_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Participant'))
                        ->authorize(fn () => auth()->user()?->can('archive Participant'))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('restore')
                        ->label(__('participant_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('participant_archive.restore_bulk_heading'))
                        ->modalDescription(__('participant_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('participant_archive.restore_bulk_confirm'))
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
                                    ->title(__('participant_archive.participants_restored'))
                                    ->body(__('participant_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('participant_archive.warning'))
                                    ->body(__('participant_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('participant_archive.no_action_taken'))
                                    ->body(__('participant_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Participant'))
                        ->authorize(fn () => auth()->user()?->can('restore Participant'))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            $skippedArchived = 0;
                            
                            foreach ($records as $record) {
                                // Skip archived records - they cannot be updated
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                $record->update(['email_verified_at' => now()]);
                                $count++;
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Activated / تم تفعيل المشاركين')
                                    ->body("{$count} participant(s) have been activated successfully. / تم تفعيل {$count} مشارك بنجاح.")
                                    ->success()
                                    ->send();
                            }
                            
                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Participants Skipped / تم تخطي المشاركين المؤرشفين')
                                    ->body("{$skippedArchived} archived participant(s) were skipped. Archived participants cannot be updated. / تم تخطي {$skippedArchived} مشارك مؤرشف. لا يمكن تحديث المشاركين المؤرشفين.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->can('update Participant')),

                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            $skippedArchived = 0;
                            
                            foreach ($records as $record) {
                                // Skip archived records - they cannot be updated
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                $record->update(['email_verified_at' => null]);
                                $count++;
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Participants Deactivated / تم إلغاء تفعيل المشاركين')
                                    ->body("{$count} participant(s) have been deactivated successfully. / تم إلغاء تفعيل {$count} مشارك بنجاح.")
                                    ->success()
                                    ->send();
                            }
                            
                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Participants Skipped / تم تخطي المشاركين المؤرشفين')
                                    ->body("{$skippedArchived} archived participant(s) were skipped. Archived participants cannot be updated. / تم تخطي {$skippedArchived} مشارك مؤرشف. لا يمكن تحديث المشاركين المؤرشفين.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->can('update Participant')),
                ]),

                ExportBulkAction::make()
                    ->exporter(ParticipantExporter::class)
                    ->columnMapping(false)
                    ->fileName('Participants_List_' . now()->format('Y-m-d')),
            ])
            ->filters([
                // Account Verified
                SelectFilter::make('is_verified')
                    ->options([
                        'verified' => 'Verified',
                        'not_verified' => 'Not Verified',
                    ])
                    ->query(
                        fn(array $data, Builder $query): Builder => $query
                            ->when($data['value'] === 'verified', fn(Builder $query) => $query->whereNotNull('email_verified_at'))
                            ->when($data['value'] === 'not_verified', fn(Builder $query) => $query->whereNull('email_verified_at'))
                    )
                    ->label('Account Verified')
                    ->placeholder('Select Account Status'),

                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status')
                    ->placeholder('Select Status'),


                SelectFilter::make('nationality')
                    ->options(Country::all()->pluck('name', 'id')->toArray())
                    ->relationship('nationality', 'name'),

                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),

                SelectFilter::make('login_by')
                    ->options([
                        'nafath' => 'Nafath SSO',
                        'credentials' => 'Email/Password',
                    ])
                    ->label('Login Method')
                    ->placeholder('Select Login Method'),

                SelectFilter::make('applications')
                    ->options(
                        array_merge(['0' => 'No Applications'],
                            Program::all()->pluck('id')->mapWithKeys(fn($value) => [$value => $value])->toArray()
                        )
                    )
                    ->query(
                        fn(array $data, Builder $query): Builder => $query
                            ->when(Arr::get($data, 'value') === '0', fn(Builder $query) => $query->doesntHave('applications'))
                            ->when($data['value'], fn(Builder $query) => $query->has('applications', $data['value']))

                    )
                    ->label('Applications')
                    ->placeholder('Select Number of Applications'),

            ])
            ->defaultSort('created_at', 'desc');
    }

    public function getTabs(): array
    {
        $baseQuery = Participant::query();

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),
            
            'active' => Tab::make(__('participant_archive.active_participants'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive Participant') || auth()->user()?->can('restore Participant')) {
            $tabs['archived'] = Tab::make(__('participant_archive.archived_participants'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
