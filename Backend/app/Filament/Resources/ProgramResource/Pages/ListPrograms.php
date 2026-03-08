<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Exports\ProgramExporter;
use App\Filament\Resources\ProgramResource;
use App\Models\Program;
use App\Models\SupervisorProgram;
use App\Models\User;
use App\Models\UserProgram;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        // if supervisor get his Programs, otherwise get all Programs.
        $user = auth()->user();
        $query = $user->isSuperAdmin();
        if ($query) {
            $table->query(Program::query());
        } else {
            $supervisorPrograms = UserProgram::where('user_id', $user->id)->get();
            $programIds = $supervisorPrograms->pluck('program_id')->toArray();
            $table->query(Program::query()->whereIn('id', $programIds));
        }

        return $table
            ->columns(Program::columns())
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label('Manage')
                    ->color('primary')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn ($record) => ProgramResource::getUrl('manage', ['record' => $record]))
                    ->authorize(fn ($record) => ProgramResource::canView($record)),

                Tables\Actions\Action::make('Set as current program')
                    ->label('Set as current')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->setAsCurrent())
                    ->visible(fn($record) => !$record->isCurrent() && !$record->isArchived()),

                Tables\Actions\Action::make('archive')
                    ->label(__('archive.archive_program'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('archive.archive_modal_heading'))
                    ->modalDescription(__('archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $approvalService = new \App\Services\ProgramApprovalService();
                        $result = $approvalService->processAction(
                            'archive',
                            ['is_archived' => true, 'program_id' => $record->id, 'title' => $record->title],
                            $record->id,
                            'Archive program request / طلب أرشفة برنامج'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                    ->body('Your archive request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            } else {
                                $record->archive();
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.program_archived'))
                                    ->body(__('archive.successfully_archived'))
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => !$record->isArchived() && ProgramResource::canArchive($record) && !$record->isCurrent()),

                Tables\Actions\Action::make('restore')
                    ->label(__('archive.restore_program'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('archive.restore_modal_heading'))
                    ->modalDescription(__('archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('archive.program_restored'))
                            ->body(__('archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && ProgramResource::canRestore($record) && !$record->isCurrent()),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize(fn ($record) => ProgramResource::canDelete($record))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Program / حذف البرنامج')
                    ->modalDescription('Are you sure you want to delete this program? This action will be submitted for approval. / هل أنت متأكد من حذف هذا البرنامج؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->action(function (Program $record) {
                        $approvalService = new \App\Services\ProgramApprovalService();
                        $result = $approvalService->processAction('delete', ['program_id' => $record->id, 'title' => $record->title], $record->id, 'Program deletion request');

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted / تم تقديم الطلب')
                                    ->body('Your deletion request has been submitted for approval. / تم تقديم طلب الحذف للموافقة.')
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            } else {
                                $record->delete();
                                \Filament\Notifications\Notification::make()
                                    ->title('Program Deleted / تم حذف البرنامج')
                                    ->body('The program has been deleted successfully. / تم حذف البرنامج بنجاح.')
                                    ->success()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                                ->danger()
                                ->send();
                        }
                    }),

            ], position: ActionsPosition::AfterColumns)

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('archive.archive_bulk_heading'))
                        ->modalDescription(__('archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('archive.archive_bulk_confirm'))
                        ->action(function ($records) {
                            $approvalService = new \App\Services\ProgramApprovalService();
                            $count = 0;
                            $alreadyArchived = 0;
                            $pendingApproval = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $result = $approvalService->processAction(
                                        'archive',
                                        ['is_archived' => true, 'program_id' => $record->id, 'title' => $record->title],
                                        $record->id,
                                        'Bulk archive program request / طلب أرشفة جماعية للبرامج'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->archive();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $alreadyArchived++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.programs_archived'))
                                    ->body(__('archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} archive requests have been submitted for approval.")
                                    ->success()
                                    ->send();
                                
                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.warning'))
                                    ->body(__('archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0 && $pendingApproval === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.no_action_taken'))
                                    ->body(__('archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Program'))
                        ->authorize(fn () => auth()->user()?->can('archive Program')),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('archive.restore_bulk_heading'))
                        ->modalDescription(__('archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('archive.restore_bulk_confirm'))
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
                                    ->title(__('archive.programs_restored'))
                                    ->body(__('archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.warning'))
                                    ->body(__('archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('archive.no_action_taken'))
                                    ->body(__('archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Program'))
                        ->authorize(fn () => auth()->user()?->can('restore Program')),

                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete Selected / حذف المحدد')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Programs / حذف البرامج المحددة')
                        ->modalDescription('Are you sure you want to delete the selected programs? This action will be submitted for approval. / هل أنت متأكد من حذف البرامج المحددة؟ سيتم تقديم هذا الإجراء للموافقة.')
                        ->action(function (Collection $records) {
                            $approvalService = new \App\Services\ProgramApprovalService();
                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($records as $record) {
                                $result = $approvalService->processAction(
                                    'delete',
                                    ['program_id' => $record->id, 'title' => $record->title],
                                    $record->id,
                                    'Bulk deletion request / طلب حذف جماعي'
                                );

                                if ($result['success']) {
                                    $successCount++;
                                } else {
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Deletion Requests Submitted / تم تقديم طلبات الحذف')
                                    ->body("{$successCount} deletion request(s) submitted for approval. / تم تقديم {$successCount} طلب حذف للموافقة.")
                                    ->success()
                                    ->send();
                                
                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($errorCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$errorCount} deletion request(s) failed. / فشل {$errorCount} طلب حذف.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->isSuperAdmin() === true)
                        ->authorize(fn () => auth()->user()?->isSuperAdmin() === true)
                ]),

                Tables\Actions\ExportBulkAction::make()->exporter(ProgramExporter::class)
                    ->columnMapping(false)
                    ->fileName('Programs_List_' . now()->format('Y-m-d'))

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_published')
                    ->label('Published')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->query(
                        fn(array $data, Builder $query): Builder => $query
                            ->when($data['value'] === 'open', fn($query) => $query->whereHas('stages', function ($q) {
                                $q->where('slug', 'registration')
                                  ->where(function ($subQ) {
                                      // Registration has started (starts_at <= now)
                                      $subQ->whereNull('starts_at')
                                           ->orWhere('starts_at', '<=', now());
                                  })
                                  ->where('ends_at', '>', now());
                            }))
                            ->when($data['value'] === 'closed', fn($query) => $query->where(function ($q) {
                                $q->whereDoesntHave('stages', function ($subQ) {
                                    $subQ->where('slug', 'registration');
                                })->orWhereHas('stages', function ($subQ) {
                                    $subQ->where('slug', 'registration')
                                         ->where(function ($statusQ) {
                                             // Registration has ended (ends_at <= now)
                                             $statusQ->where('ends_at', '<=', now())
                                                     // OR registration hasn't started yet (starts_at > now)
                                                     ->orWhere('starts_at', '>', now());
                                         });
                                });
                            }))
                    )
            ])
            ->defaultSort('created_at', 'desc');


    }

    public function getTabs(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $baseQuery = Program::query();
        } else {
            $programIds = UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();

            $baseQuery = Program::query()->whereIn('id', $programIds);
        }

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => Tab::make(__('archive.active_programs'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', false)),

            'open' => Tab::make('Open')
                ->badge((clone $baseQuery)->active()->open()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', false)->open()),

            'closed' => Tab::make('Closed')
                ->badge((clone $baseQuery)->active()->closed()->count())
                ->modifyQueryUsing(fn($query) => $query->where('is_archived', false)->closed()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive Program') || auth()->user()?->can('restore Program')) {
            $tabs['archived'] = Tab::make(__('archive.archived_programs'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }

}
