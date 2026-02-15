<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Services\ProgramApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this program
        if (!CompetitionResource::canEdit($record)) {
            abort(404, 'Program not found / البرنامج غير موجود');
        }

        return $record;
    }

    /**
     * Mount the component and check if record is archived
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Prevent editing archived records - they can only be deleted or restored
        if ($this->record->isArchived()) {
            Notification::make()
                ->title('Cannot Edit Archived Program / لا يمكن تعديل برنامج مؤرشف')
                ->body('Archived programs cannot be edited. You can only delete or restore them. / لا يمكن تعديل البرامج المؤرشفة. يمكنك فقط حذفها أو استعادتها.')
                ->warning()
                ->send();

            $this->redirect(CompetitionResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn () => CompetitionResource::canDelete($this->record))
                ->modalHeading('Delete Program / حذف البرنامج')
                ->modalDescription('Are you sure you want to delete this program? This action will be submitted for approval. / هل أنت متأكد من حذف هذا البرنامج؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function () {
                    // Check if approval workflow exists for competition deletion
                    $approvalService = new ProgramApprovalService();
                    
                    $result = $approvalService->processAction(
                        'delete',
                        [
                            'competition_id' => $this->record->id, 
                            'title' => $this->record->title,
                            'old_values' => $this->record->toArray(), // Store current values for reference
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
                            // Execute immediately if no workflow
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
                }),
            Actions\Action::make('archive')
                ->label('Archive / أرشفة')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->authorize(fn () => CompetitionResource::canArchive($this->record))
                ->visible(fn () => !$this->record->isArchived())
                ->action(function () {
                    $this->handleArchiveAction();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if approval workflow exists for competition update
        $approvalService = new ProgramApprovalService();
        
        // Store old values for comparison in approval request
        //$oldValues = $this->record->only(array_keys($data));
        $oldValues = $this->record->only(array_keys($data));

// إضافة القيم العربية بشكل منفصل
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
        // Merge data: $data (new values) takes priority, then add competition_id
        $actionData = array_merge($data, [
            'competition_id' => $this->record->id,
            'old_values' => $oldValues, // Store old values for before/after comparison
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
                // Success - show notification and redirect
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
                'old_values' => ['is_archived' => $this->record->is_archived ?? false], // Store current archive status
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
                // Execute immediately if no workflow
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