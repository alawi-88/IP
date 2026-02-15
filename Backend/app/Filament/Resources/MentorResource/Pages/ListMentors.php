<?php

namespace App\Filament\Resources\MentorResource\Pages;

use App\Filament\Exports\MentorExporter;
use App\Filament\Resources\MentorResource;
use App\Models\Mentor;
use App\Notifications\Mentor\MentorApproved;
use App\Notifications\Mentor\MentorRejected;
use App\Notifications\Mentor\MentorDeactivated;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Builder;

class ListMentors extends ListRecords
{
    protected static string $resource = MentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Mentor::byCompetition())
            ->columns(Mentor::columns())
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->placeholder('All Statuses'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Mentor')
                    ->modalDescription('Are you sure you want to approve this mentor? They will receive an email notification.')
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        
                        $record->notify(new MentorApproved($record));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Mentor Approved')
                            ->body('The mentor has been approved and notified via email.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && $record->status === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Mentor')
                    ->modalDescription('Are you sure you want to reject this mentor? They will receive an email notification.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->placeholder('Enter reason for rejection (optional)')
                            ->maxLength(1000)
                    ])
                    ->modalSubmitActionLabel('Yes, Reject')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejected_at' => now(),
                            'approved_by' => auth()->id(),
                            'rejection_reason' => $data['reason'] ?? null,
                        ]);
                        
                        $record->notify(new MentorRejected($record, $data['reason'] ?? null));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Mentor Rejected')
                            ->body('The mentor has been rejected and notified via email.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && $record->status === 'pending'),

                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->color('warning')
                    ->icon('heroicon-o-eye-slash')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Mentor')
                    ->modalDescription('Are you sure you want to deactivate this mentor? They will no longer be visible to participants.')
                    ->modalSubmitActionLabel('Yes, Deactivate')
                    ->action(function ($record) {
                        $record->update(['is_visible' => false]);
                        
                        // Send deactivation notification to mentor
                        $record->notify(new MentorDeactivated($record));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Mentor Deactivated')
                            ->body('The mentor has been deactivated and notified via email.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && $record->is_visible && auth()->user()?->can('update Mentor')),

                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->color('success')
                    ->icon('heroicon-o-eye')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Mentor')
                    ->modalDescription('Are you sure you want to activate this mentor? They will become visible to participants.')
                    ->modalSubmitActionLabel('Yes, Activate')
                    ->action(function ($record) {
                        $record->update(['is_visible' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Mentor Activated')
                            ->body('The mentor has been activated and is now visible to participants.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && !$record->is_visible && auth()->user()?->can('update Mentor')),

                Tables\Actions\Action::make('archive')
                    ->label(__('mentor_archive.archive_mentor'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('mentor_archive.confirm_archive'))
                    ->modalDescription(__('mentor_archive.archive_confirmation'))
                    ->modalSubmitActionLabel(__('mentor_archive.confirm_archive'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('mentor_archive.mentor_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && MentorResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('mentor_archive.restore_mentor'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('mentor_archive.confirm_restore'))
                    ->modalDescription(__('mentor_archive.restore_confirmation'))
                    ->modalSubmitActionLabel(__('mentor_archive.confirm_restore'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('mentor_archive.mentor_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && MentorResource::canRestore($record)),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
                Tables\Actions\DeleteAction::make(),
            ], position: ActionsPosition::AfterColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('mentor_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('mentor_archive.confirm_archive'))
                        ->modalDescription(__('mentor_archive.archive_selected_confirmation'))
                        ->modalSubmitActionLabel(__('mentor_archive.confirm_archive'))
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
                                        ->title(__('mentor_archive.mentors_archived'))
                                        ->body(__('mentor_archive.successfully_archived_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('mentor_archive.warning'))
                                        ->body(__('mentor_archive.already_archived_count', ['count' => $alreadyArchived]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('mentor_archive.no_action_taken'))
                                        ->body(__('mentor_archive.all_selected_already_archived'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('mentor_archive.failed_to_archive_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Mentor') ?? false)
                        ->authorize(fn () => auth()->user()?->can('archive Mentor') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('mentor_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('mentor_archive.confirm_restore'))
                        ->modalDescription(__('mentor_archive.restore_selected_confirmation'))
                        ->modalSubmitActionLabel(__('mentor_archive.confirm_restore'))
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
                                        ->title(__('mentor_archive.mentors_restored'))
                                        ->body(__('mentor_archive.successfully_restored_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('mentor_archive.warning'))
                                        ->body(__('mentor_archive.already_active_count', ['count' => $alreadyActive]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('mentor_archive.no_action_taken'))
                                        ->body(__('mentor_archive.all_selected_already_active'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('mentor_archive.failed_to_restore_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Mentor') ?? false)
                        ->authorize(fn () => auth()->user()?->can('restore Mentor') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->color('warning')
                        ->icon('heroicon-o-eye-slash')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Mentors')
                        ->modalDescription('Are you sure you want to deactivate the selected mentors? They will no longer be visible to participants.')
                        ->modalSubmitActionLabel('Yes, Deactivate Selected')
                        ->action(function ($records) {
                            try {
                                $count = 0;
                                $alreadyInvisible = 0;
                                $skippedArchived = 0;
                                
                                foreach ($records as $record) {
                                    // Skip archived records - they cannot be updated
                                    if ($record->isArchived()) {
                                        $skippedArchived++;
                                        continue;
                                    }
                                    
                                    if ($record->is_visible) {
                                        $record->update(['is_visible' => false]);
                                        // Send deactivation notification to mentor
                                        $record->notify(new MentorDeactivated($record));
                                        $count++;
                                    } else {
                                        $alreadyInvisible++;
                                    }
                                }
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Mentors Deactivated')
                                        ->body("Successfully deactivated {$count} mentor(s).")
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyInvisible > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Notice')
                                        ->body("{$alreadyInvisible} mentor(s) were already invisible.")
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($skippedArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Archived Mentors Skipped / تم تخطي المرشدين المؤرشفين')
                                        ->body("{$skippedArchived} archived mentor(s) were skipped. Archived mentors cannot be updated. / تم تخطي {$skippedArchived} مرشد مؤرشف. لا يمكن تحديث المرشدين المؤرشفين.")
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to Deactivate')
                                    ->body('There was an error deactivating the selected mentors.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Mentor') ?? false)
                        ->authorize(fn () => auth()->user()?->can('update Mentor') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->color('success')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Mentors')
                        ->modalDescription('Are you sure you want to activate the selected mentors? They will become visible to participants.')
                        ->modalSubmitActionLabel('Yes, Activate Selected')
                        ->action(function ($records) {
                            try {
                                $count = 0;
                                $alreadyVisible = 0;
                                $skippedArchived = 0;
                                
                                foreach ($records as $record) {
                                    // Skip archived records - they cannot be updated
                                    if ($record->isArchived()) {
                                        $skippedArchived++;
                                        continue;
                                    }
                                    
                                    if (!$record->is_visible) {
                                        $record->update(['is_visible' => true]);
                                        $count++;
                                    } else {
                                        $alreadyVisible++;
                                    }
                                }
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Mentors Activated')
                                        ->body("Successfully activated {$count} mentor(s).")
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyVisible > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Notice')
                                        ->body("{$alreadyVisible} mentor(s) were already visible.")
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($skippedArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Archived Mentors Skipped / تم تخطي المرشدين المؤرشفين')
                                        ->body("{$skippedArchived} archived mentor(s) were skipped. Archived mentors cannot be updated. / تم تخطي {$skippedArchived} مرشد مؤرشف. لا يمكن تحديث المرشدين المؤرشفين.")
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to Activate')
                                    ->body('There was an error activating the selected mentors.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Mentor') ?? false)
                        ->authorize(fn () => auth()->user()?->can('update Mentor') ?? false)
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Mentor')),
                ]),

                Tables\Actions\ExportBulkAction::make()
                    ->exporter(MentorExporter::class)
                    ->columnMapping(false)
                    ->fileName('Mentors_List_' . now()->format('Y-m-d')),
            ]);
    }

    public function getTabs(): array
    {
        $baseQuery = Mentor::byCompetition();

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),
            
            'pending' => Tab::make('Pending Approval')
                ->badge((clone $baseQuery)->where('status', 'pending')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'pending')),
            
            'approved' => Tab::make('Approved')
                ->badge((clone $baseQuery)->where('status', 'approved')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'approved')),
            
            'rejected' => Tab::make('Rejected')
                ->badge((clone $baseQuery)->where('status', 'rejected')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'rejected')),
            
            'active' => Tab::make(__('mentor_archive.active_mentors'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', false)),
        ];

        // Add archived tab if user has archive/restore permissions
        if (auth()->user()?->can('archive Mentor') || auth()->user()?->can('restore Mentor')) {
            $tabs['archived'] = Tab::make(__('mentor_archive.archived_mentors'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', true));
        }

        return $tabs;
    }
}
