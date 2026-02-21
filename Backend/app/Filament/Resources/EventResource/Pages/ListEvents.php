<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Exports\EventExporter;
use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Builder;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Event::byProgram())
            ->columns(Event::columns())
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('archive')
                    ->label(__('event_archive.archive_event'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('event_archive.archive_modal_heading'))
                    ->modalDescription(__('event_archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('event_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title(__('event_archive.event_archived'))
                            ->body(__('event_archive.successfully_archived'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => !$record->isArchived() && EventResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('event_archive.restore_event'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('event_archive.restore_modal_heading'))
                    ->modalDescription(__('event_archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('event_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('event_archive.event_restored'))
                            ->body(__('event_archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && EventResource::canRestore($record)),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),
                Tables\Actions\DeleteAction::make(),
            ], position: ActionsPosition::AfterColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('event_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('event_archive.archive_bulk_heading'))
                        ->modalDescription(__('event_archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('event_archive.archive_bulk_confirm'))
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
                                    ->title(__('event_archive.events_archived'))
                                    ->body(__('event_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('event_archive.warning'))
                                    ->body(__('event_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('event_archive.no_action_taken'))
                                    ->body(__('event_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Event'))
                        ->authorize(fn () => auth()->user()?->can('archive Event')),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('event_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('event_archive.restore_bulk_heading'))
                        ->modalDescription(__('event_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('event_archive.restore_bulk_confirm'))
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
                                    ->title(__('event_archive.events_restored'))
                                    ->body(__('event_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('event_archive.warning'))
                                    ->body(__('event_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }
                            
                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('event_archive.no_action_taken'))
                                    ->body(__('event_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Event'))
                        ->authorize(fn () => auth()->user()?->can('restore Event')),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Event')),

                    Tables\Actions\BulkAction::make('markAsPublished')
                        ->label('Mark as Published')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            $skippedArchived = 0;
                            
                            foreach ($records as $record) {
                                // Skip archived records - they cannot be updated
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                $record->update(['is_visible' => true]);
                                $count++;
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Events Published / تم نشر الأحداث')
                                    ->body("{$count} event(s) have been published successfully. / تم نشر {$count} حدث بنجاح.")
                                    ->success()
                                    ->send();
                            }
                            
                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Events Skipped / تم تخطي الأحداث المؤرشفة')
                                    ->body("{$skippedArchived} archived event(s) were skipped. Archived events cannot be updated. / تم تخطي {$skippedArchived} حدث مؤرشف. لا يمكن تحديث الأحداث المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Publish selected events?')
                        ->modalDescription('This will mark the selected events as published.')
                        ->modalSubmitActionLabel('Yes, publish')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(fn () => auth()->user()?->can('update Event')),

                    Tables\Actions\BulkAction::make('markAsUnpublished')
                        ->label('Mark as Unpublished')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = 0;
                            $skippedArchived = 0;
                            
                            foreach ($records as $record) {
                                // Skip archived records - they cannot be updated
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }
                                $record->update(['is_visible' => false]);
                                $count++;
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Events Unpublished / تم إلغاء نشر الأحداث')
                                    ->body("{$count} event(s) have been unpublished successfully. / تم إلغاء نشر {$count} حدث بنجاح.")
                                    ->success()
                                    ->send();
                            }
                            
                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Events Skipped / تم تخطي الأحداث المؤرشفة')
                                    ->body("{$skippedArchived} archived event(s) were skipped. Archived events cannot be updated. / تم تخطي {$skippedArchived} حدث مؤرشف. لا يمكن تحديث الأحداث المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Unpublish selected events?')
                        ->modalDescription('This will mark the selected events as unpublished.')
                        ->modalSubmitActionLabel('Yes, unpublish')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(fn () => auth()->user()?->can('update Event')),
                ]),

                Tables\Actions\ExportBulkAction::make()
                    ->exporter(EventExporter::class)
                    ->columnMapping(false)
                    ->fileName('Events_List_' . now()->format('Y-m-d')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->placeholder('All Locations')
                    ->options([
                        'onsite' => 'Onsite',
                        'virtual' => 'Virtual',
                    ])
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function getTabs(): array
    {
        $baseQuery = Event::byProgram();

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => Tab::make(__('event_archive.active_events'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),

            'upcoming' => Tab::make('Upcoming')
                ->badge((clone $baseQuery)->active()->where('badge', 'upcoming')->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()->where('badge', 'upcoming')),

            'completed' => Tab::make('Completed')
                ->badge((clone $baseQuery)->active()->where('badge', 'completed')->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()->where('badge', 'completed')),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive Event') || auth()->user()?->can('restore Event')) {
            $tabs['archived'] = Tab::make(__('event_archive.archived_events'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
