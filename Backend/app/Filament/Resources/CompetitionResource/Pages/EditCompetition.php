<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Models\Competition;
use App\Services\ProgramApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     * Now allows viewing for all users with access (not just editors)
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Allow access if user can view OR edit this program
        if (!CompetitionResource::canView($record) && !CompetitionResource::canEdit($record)) {
            abort(404, 'Program not found / البرنامج غير موجود');
        }

        return $record;
    }

    /**
     * Mount the component - show warning for archived records but stay on this page
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Show warning for archived records but don't redirect
        if ($this->record->isArchived()) {
            Notification::make()
                ->title('Archived Program / برنامج مؤرشف')
                ->body('This program is archived. You can only delete or restore it. / هذا البرنامج مؤرشف. يمكنك فقط حذفه أو استعادته.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    /**
     * Disable form for archived records
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        // Make form read-only for archived records or users without edit permission
        if ($this->record->isArchived() || !CompetitionResource::canEdit($this->record)) {
            $this->form->disabled();
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Restore action for archived records
        $actions[] = Actions\Action::make('restore')
            ->label('Restore / استعادة')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Restore Program / استعادة البرنامج')
            ->modalDescription('Are you sure you want to restore this program? / هل أنت متأكد من استعادة هذا البرنامج؟')
            ->authorize(fn () => CompetitionResource::canRestore($this->record))
            ->visible(fn () => $this->record->isArchived())
            ->action(function () {
                $this->record->restore();
                Notification::make()
                    ->title('Program Restored / تم استعادة البرنامج')
                    ->body('The program has been restored successfully. / تم استعادة البرنامج بنجاح.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.competitions.index'));
            });

        // Delete action
        $actions[] = Actions\Action::make('delete')
            ->label('Delete / حذف')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize(fn () => CompetitionResource::canDelete($this->record))
            ->modalHeading('Delete Program / حذف البرنامج')
            ->modalDescription('Are you sure you want to delete this program? This action will be submitted for approval. / هل أنت متأكد من حذف هذا البرنامج؟ سيتم تقديم هذا الإجراء للموافقة.')
            ->action(function () {
                $approvalService = new ProgramApprovalService();

                $result = $approvalService->processAction(
                    'delete',
                    [
                        'competition_id' => $this->record->id,
                        'title' => $this->record->title,
                        'old_values' => $this->record->toArray(),
                    ],
                    $this->record->id,
                    'Program deletion request / طلب حذف البرنامج'
                );

                if ($result['success']) {
                    if ($result['requires_approval']) {
                        Notification::make()
                            ->title('Deletion Request Submitted / تم تقديم طلب الحذف')
                            ->body('Your competition deletion request has been submitted for approval.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                    } else {
                        $this->record->delete();
                        Notification::make()
                            ->title('Competition Deleted / تم حذف المسابقة')
                            ->body('The competition has been deleted successfully.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.competitions.index'));
                    }
                } else {
                    Notification::make()
                        ->title('Error / خطأ')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            });

        // Archive action (only for non-archived records)
        $actions[] = Actions\Action::make('archive')
            ->label('Archive / أرشفة')
            ->icon('heroicon-o-archive-box')
            ->color('warning')
            ->requiresConfirmation()
            ->authorize(fn () => CompetitionResource::canArchive($this->record))
            ->visible(fn () => !$this->record->isArchived())
            ->action(function () {
                $this->handleArchiveAction();
            });

        return $actions;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Block saves for archived records
        if ($this->record->isArchived()) {
            Notification::make()
                ->title('Cannot Edit / لا يمكن التعديل')
                ->body('Archived programs cannot be edited.')
                ->danger()
                ->send();
            $this->halt();
        }

        $approvalService = new ProgramApprovalService();

        $oldValues = $this->record->only(array_keys($data));

        // Add Arabic translations separately
        $oldValues['title_ar'] = is_array($this->record->title)
            ? ($this->record->title['ar'] ?? '')
            : (method_exists($this->record, 'getTranslation')
                ? $this->record->getTranslation('title', 'ar', false)
                : '');

        $oldValues['about_ar'] = is_array($this->record->about)
            ? ($this->record->about['ar'] ?? '')
            : (method_exists($this->record, 'getTranslation')
                ? $this->record->getTranslation('about', 'ar', false)
                : '');

        $oldValues['terms_and_conditions_ar'] = is_array($this->record->terms_and_conditions)
            ? ($this->record->terms_and_conditions['ar'] ?? '')
            : (method_exists($this->record, 'getTranslation')
                ? $this->record->getTranslation('terms_and_conditions', 'ar', false)
                : '');

        $actionData = array_merge($data, [
            'competition_id' => $this->record->id,
            'old_values' => $oldValues,
        ]);

        $result = $approvalService->processAction(
            'update',
            $actionData,
            $this->record->id,
            'Program update request / طلب تحديث برنامج'
        );

        if ($result['success']) {
            if ($result['requires_approval']) {
                Notification::make()
                    ->title('Update Request Submitted / تم تقديم طلب التحديث')
                    ->body('Your competition update request has been submitted for approval.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            } else {
                Notification::make()
                    ->title('Competition Updated Successfully / تم تحديث المسابقة بنجاح')
                    ->body('The competition has been updated successfully.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.competitions.index'));
                $this->halt();
            }
        } else {
            Notification::make()
                ->title('Error / خطأ')
                ->body($result['message'])
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function handleArchiveAction(): void
    {
        $approvalService = new ProgramApprovalService();

        $result = $approvalService->processAction(
            'archive',
            [
                'is_archived' => true,
                'competition_id' => $this->record->id,
                'title' => $this->record->title,
                'old_values' => ['is_archived' => $this->record->is_archived ?? false],
            ],
            $this->record->id,
            'Program archive request / طلب أرشفة البرنامج'
        );

        if ($result['success']) {
            if ($result['requires_approval']) {
                Notification::make()
                    ->title('Archive Request Submitted / تم تقديم طلب الأرشفة')
                    ->body('Your competition archive request has been submitted for approval.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
            } else {
                $this->record->update(['is_archived' => true]);
                Notification::make()
                    ->title('Competition Archived / تم أرشفة المسابقة')
                    ->body('The competition has been archived successfully.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.competitions.index'));
            }
        } else {
            Notification::make()
                ->title('Error / خطأ')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
