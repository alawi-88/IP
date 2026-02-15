<?php

namespace App\Filament\Resources\WinnerResource\Pages;

use App\Filament\Resources\WinnerResource;
use App\Services\WinnerApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWinner extends EditRecord
{
    protected static string $resource = WinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Winner / حذف الفائز')
                ->modalDescription('Are you sure you want to delete this winner? This action will be submitted for approval. / هل أنت متأكد من حذف هذا الفائز؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function () {
                    $approvalService = new WinnerApprovalService();

                    $result = $approvalService->processAction(
                        'delete',
                        [
                            'winner_id' => $this->record->id,
                            'old_values' => $this->record->toArray(),
                        ],
                        $this->record->id,
                        'Winner deletion request / طلب حذف فائز'
                    );

                    if ($result['success'] ?? false) {
                        if ($result['requires_approval'] ?? false) {
                            Notification::make()
                                ->title('Deletion Request Submitted / تم تقديم طلب الحذف')
                                ->body('Your winner deletion request has been submitted for approval. / تم تقديم طلب حذف الفائز للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            $this->halt();
                        }

                        // No workflow => delete immediately
                        $this->record->delete();
                        Notification::make()
                            ->title('Winner Deleted / تم حذف الفائز')
                            ->body('The winner has been deleted successfully. / تم حذف الفائز بنجاح.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                        $this->halt();
                    }

                    Notification::make()
                        ->title('Error / خطأ')
                        ->body($result['message'] ?? 'Failed to submit deletion request / فشل في تقديم طلب الحذف')
                        ->danger()
                        ->send();

                    $this->halt();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $approvalService = new WinnerApprovalService();

        $oldValues = $this->record->only(array_keys($data));

        $actionData = array_merge($data, [
            'winner_id' => $this->record->id,
            'old_values' => $oldValues,
        ]);

        $result = $approvalService->processAction(
            'update',
            $actionData,
            $this->record->id,
            'Winner update request / طلب تحديث فائز'
        );

        if ($result['success'] ?? false) {
            if ($result['requires_approval'] ?? false) {
                Notification::make()
                    ->title('Update Request Submitted / تم تقديم طلب التحديث')
                    ->body('Your winner update request has been submitted for approval. / تم تقديم طلب تحديث الفائز للموافقة.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            }

            // No workflow => proceed with normal save
            return $data;
        }

        Notification::make()
            ->title('Error / خطأ')
            ->body($result['message'] ?? 'Failed to submit approval request / فشل في تقديم طلب الموافقة')
            ->danger()
            ->send();

        $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
