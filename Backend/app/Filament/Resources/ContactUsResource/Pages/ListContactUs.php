<?php

namespace App\Filament\Resources\ContactUsResource\Pages;

use App\Filament\Exports\ContactUsExporter;
use App\Filament\Resources\ContactUsResource;
use App\Models\ContactUs;
use App\Models\Participant;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListContactUs extends ListRecords
{
    protected static string $resource = ContactUsResource::class;

    protected static ?string $navigationLabel = 'Contact Us';

    /**
     * @return string
     */
    public function getHeading(): string
    {
        return 'Contact Us';
    }



    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        $baseQuery = ContactUs::query()->where('model_type', Participant::class);

        return $table
            ->query($baseQuery)
            ->columns(ContactUs::columns())
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'resolved' => 'Resolved',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => auth()->user()?->can('view ContactUs') ?? false),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('delete ContactUs') ?? false),

                Tables\Actions\Action::make('archive')
                    ->label(__('contact_archive.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('contact_archive.confirm_archive'))
                    ->action(function (ContactUs $record) {
                        try {
                            $record->archive();
                            \Filament\Notifications\Notification::make()
                                ->title(__('contact_archive.archived_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('contact_archive.archive_failed'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (ContactUs $record) => ContactUsResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('contact_archive.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('contact_archive.confirm_restore'))
                    ->action(function (ContactUs $record) {
                        try {
                            $record->restore();
                            \Filament\Notifications\Notification::make()
                                ->title(__('contact_archive.restored_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('contact_archive.restore_failed'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (ContactUs $record) => ContactUsResource::canRestore($record)),

                Tables\Actions\Action::make('reply')
                    ->label(fn(ContactUs $record) => $record->isReplied() ? 'View Reply' : 'Reply')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->modalHeading(fn(ContactUs $record) => $record->isReplied() ? 'View Reply' : 'Reply to Contact Us')
                    ->form(
                        fn(ContactUs $record) => $record->isReplied()
                            ? [
                                \Filament\Forms\Components\Placeholder::make('replied_at')
                                    ->label('Replay Date')
                                    ->content($record->replied_at ? Carbon::parse($record->replied_at)->format('Y-m-d H:i') : '-'),
                                \Filament\Forms\Components\RichEditor::make('reply')
                                    ->label('Reply')
                                    ->disabled()
                                    ->default($record->reply),
                            ]
                            : [
                                \Filament\Forms\Components\RichEditor::make('reply')
                                    ->label('Reply')
                                    ->required(),
                            ]
                    )
                    ->action(function (ContactUs $record, array $data) {
                        if (!$record->isReplied()) {
                            $record->reply = $data['reply'];
                            $record->status = 'resolved';
                            $record->replied_by = auth()->id();
                            $record->replied_at = now();
                            $record->save();
                        }
                    })
                    ->modalSubmitAction(fn(ContactUs $record) => $record->reply ? false : null)
                    ->modalCancelAction(fn() => null)
                    ->visible(fn (ContactUs $record) => !$record->isArchived() && auth()->user()?->can('update ContactUs') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete ContactUs') ?? false),

                    Tables\Actions\BulkAction::make('archive_selected')
                        ->label(__('contact_archive.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('contact_archive.confirm_archive_selected'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyArchived = 0;
                            
                            try {
                                foreach ($records as $record) {
                                    if ($record->isArchived()) {
                                        $alreadyArchived++;
                                    } else {
                                        if (ContactUsResource::canArchive($record)) {
                                            $record->archive();
                                            $count++;
                                        }
                                    }
                                }
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.archived_selected_successfully'))
                                        ->body(__('contact_archive.successfully_archived_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.warning'))
                                        ->body(__('contact_archive.already_archived_count', ['count' => $alreadyArchived]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyArchived > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.no_action_taken'))
                                        ->body(__('contact_archive.all_selected_already_archived'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('contact_archive.archive_selected_failed'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->can('archive ContactUs') ?? false),

                    Tables\Actions\BulkAction::make('restore_selected')
                        ->label(__('contact_archive.restore_selected'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('contact_archive.confirm_restore_selected'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            
                            try {
                                foreach ($records as $record) {
                                    if (!$record->isArchived()) {
                                        $alreadyActive++;
                                    } else {
                                        if (ContactUsResource::canRestore($record)) {
                                            $record->restore();
                                            $count++;
                                        }
                                    }
                                }
                                
                                if ($count > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.restored_selected_successfully'))
                                        ->body(__('contact_archive.successfully_restored_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }
                                
                                if ($alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.warning'))
                                        ->body(__('contact_archive.already_active_count', ['count' => $alreadyActive]))
                                        ->warning()
                                        ->send();
                                }
                                
                                if ($count === 0 && $alreadyActive > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title(__('contact_archive.no_action_taken'))
                                        ->body(__('contact_archive.all_selected_already_active'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('contact_archive.restore_selected_failed'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->can('restore ContactUs') ?? false),
                ]),
                ExportBulkAction::make()
                    ->exporter(ContactUsExporter::class)
                    ->columnMapping(false)
                    ->fileName('Contact_Us_List_' . now()->format('Y-m-d'))
                    ->label('Export Contact Us')
                    ->modalHeading('Export Contact Us')
                    ->visible(fn () => auth()->user()?->can('update ContactUs') ?? false),
            ])
            ->emptyStateHeading('No Contact Us submissions');
    }

    public function getTabs(): array
    {
        $baseQuery = ContactUs::query()->where('model_type', Participant::class);

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query) => clone $baseQuery),
            
            'active' => Tab::make(__('contact_archive.active_contact_us'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->active()),
        ];

        // Add archived tab if user has archive/restore permissions
        if (auth()->user()?->can('archive ContactUs') || auth()->user()?->can('restore ContactUs')) {
            $tabs['archived'] = Tab::make(__('contact_archive.archived_contact_us'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
