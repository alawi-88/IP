<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use App\Services\ProgramApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCompetition extends CreateRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if approval workflow exists for program creation
        $approvalService = new ProgramApprovalService();
        
        if ($approvalService->hasWorkflowForAction('create')) {
            // Create approval request instead of creating directly
            $result = $approvalService->processAction(
                'create',
                $data,
                null,
                'Program creation request / طلب إنشاء برنامج'
            );

            if ($result['success'] && $result['requires_approval']) {
                Notification::make()
                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                    ->body('Your program creation request has been submitted for approval. You will be notified once approved.')
                    ->success()
                    ->send();

                // Redirect to the approval requests list
                $this->redirect(route('filament.admin.resources.my-requests.index'));
                $this->halt();
            } else {
                Notification::make()
                    ->title('Error / خطأ')
                    ->body($result['message'])
                    ->danger()
                    ->send();
                
                $this->halt();
            }
        } else {
            // No workflow exists, execute immediately
            $result = $approvalService->processAction(
                'create',
                $data,
                null,
                'Program creation request / طلب إنشاء برنامج'
            );

            if (!$result['success']) {
                Notification::make()
                    ->title('Error / خطأ')
                    ->body($result['message'])
                    ->danger()
                    ->send();

                $this->halt();
            }

            // Success - show notification and redirect
            Notification::make()
                ->title('Program Created Successfully / تم إنشاء البرنامج بنجاح')
                ->body('The program has been created successfully.')
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.competitions.index'));
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}