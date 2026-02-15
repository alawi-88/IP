<?php

namespace App\Filament\Resources\WinnerResource\Pages;

use App\Filament\Resources\WinnerResource;
use App\Services\WinnerApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateWinner extends CreateRecord
{
    protected static string $resource = WinnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $approvalService = new WinnerApprovalService();

        $result = $approvalService->processAction(
            'create',
            $data,
            null,
            'Winner creation request / طلب إنشاء فائز'
        );

        if ($result['success'] ?? false) {
            if ($result['requires_approval'] ?? false) {
                Notification::make()
                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                    ->body('Your winner creation request has been submitted for approval. / تم تقديم طلب إنشاء الفائز للموافقة.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            }

            // No workflow => proceed with normal create
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
