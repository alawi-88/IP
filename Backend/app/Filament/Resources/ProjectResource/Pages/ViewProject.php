<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\CommitteeJudge;
use App\Models\JudgeProject;
use App\Models\Project;
use App\Services\AiEvaluationService;
use App\Services\ProjectApprovalService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiEvaluation')
                ->label('AI Evaluation / تقييم بالذكاء الاصطناعي')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => 
                    !$this->record->isArchived()
                    && $this->hasAiEvaluationPrerequisites()
                    && data_get($this->record->ai_evaluation_response, 'status') !== 'completed'
                    && $this->record->ai_evaluated_at === null
                )
                ->action(function () {
                    $service = new AiEvaluationService();
                    $answers = $this->getFormSubmissionsArray();
                    $result = $service->evaluate(
                        $this->record->form_id,
                        $answers,
                        $this->record->id,
                        'project'
                    );

                    if (!$result['success']) {
                        Notification::make()
                            ->title('AI Evaluation Failed')
                            ->body($result['message'] ?? 'Unable to complete AI evaluation.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $response = $result['response'] ?? [];
                    
                    // Calculate AI scores similar to ProcessProjectAiEvaluation job
                    $config = \App\Models\FormAiScoringConfig::where('form_id', $this->record->form_id)->first();
                    $criteriaData = collect(data_get($response, 'data.criteria', []));
                    
                    $totalScore = $criteriaData->sum(fn($criterion) => (float) data_get($criterion, 'totalScore', 0));
                    $maxWeight = $criteriaData->sum(fn($criterion) => (float) data_get($criterion, 'maxWeight', 0));
                    
                    $targetTotalWeight = $config?->total_weight ?? $maxWeight;
                    
                    $normalizedScore = $maxWeight > 0
                        ? round(($totalScore / $maxWeight) * $targetTotalWeight, 2)
                        : null;
                    
                    $payload = $response;
                    $payload['status'] = 'completed';
                    $payload['meta'] = [
                        'total_score' => $totalScore,
                        'max_weight' => $maxWeight,
                        'target_total_weight' => $targetTotalWeight,
                        'normalized_score' => $normalizedScore,
                    ];

                    $this->record->update([
                        'ai_evaluation_response' => $payload,
                        'ai_evaluated_at' => now(),
                        'total_score' => $normalizedScore ?? $totalScore,
                    ]);

                    Notification::make()
                        ->title('AI Evaluation Completed')
                        ->body('AI response saved successfully.')
                        ->success()
                        ->send();
                }),

            Action::make('update status')
                ->label('Update Status')
                ->color('primary')
                ->icon('heroicon-o-pencil')
                ->form(fn (Project $record) => $record->updateStatusForm())
                ->requiresConfirmation()
                ->modalHeading('Update Status / تحديث الحالة')
                ->modalDescription('Are you sure you want to update this project status? This action will be submitted for approval. / هل أنت متأكد من تحديث حالة هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function (array $data, Project $record) {
                    $approvalService = new ProjectApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        [
                            'status' => $data['status'],
                            'project_id' => $record->id,
                            'project_name' => data_get($record->form_submissions, 'project_name', 'N/A'),
                        ],
                        $record->id,
                        'Update project status request / طلب تحديث حالة مشروع'
                    );

                    if (!($result['success'] ?? false)) {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (($result['requires_approval'] ?? false) === true) {
                        Notification::make()
                            ->title('Request Submitted / تم تقديم الطلب')
                            ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                        return;
                    }

                    $record->setStatusAs($data['status']);
                    Notification::make()
                        ->title('Status Updated / تم تحديث الحالة')
                        ->body('Project status updated successfully. / تم تحديث حالة المشروع بنجاح.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !$this->record->isArchived() && auth()->user()->can('update Project')),

            Action::make('assign to judges')
                ->label('Assign to Judges')
                ->color('primary')
                ->icon('heroicon-o-users')
                ->form(fn(Project $record) => Project::assignToJudgesForm($record))
                ->requiresConfirmation()
                ->modalHeading('Assign Judges / تعيين الحكام')
                ->modalDescription('Are you sure you want to assign judges to this project? This action will be submitted for approval. / هل أنت متأكد من تعيين الحكام لهذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function (array $data, Project $record) {
                    $approvalService = new ProjectApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        [
                            'judge_ids' => $data['judges'],
                            'project_id' => $record->id,
                            'project_name' => data_get($record->form_submissions, 'project_name', 'N/A'),
                        ],
                        $record->id,
                        'Assign judges request / طلب تعيين حكام'
                    );

                    if (!($result['success'] ?? false)) {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (($result['requires_approval'] ?? false) === true) {
                        Notification::make()
                            ->title('Request Submitted / تم تقديم الطلب')
                            ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                        return;
                    }

                    $record->assignToJudges($data['judges']);
                    Notification::make()
                        ->title('Judges Assigned / تم تعيين الحكام')
                        ->body('Judges have been assigned successfully. / تم تعيين الحكام بنجاح.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !$this->record->isArchived() && auth()->user()->can('update Project')),

            Action::make('assign to committee')
                ->label('Assign to Committee')
                ->color('primary')
                ->icon('heroicon-o-user-circle')
                ->form(fn(Project $record) => Project::assignToCommitteeForm($record))
                ->requiresConfirmation()
                ->modalHeading('Assign Committee / تعيين اللجنة')
                ->modalDescription('Are you sure you want to assign a committee to this project? This action will be submitted for approval. / هل أنت متأكد من تعيين لجنة لهذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->action(function (array $data, Project $record) {
                    $approvalService = new ProjectApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        [
                            'committee_id' => $data['committees'],
                            'project_id' => $record->id,
                            'project_name' => data_get($record->form_submissions, 'project_name', 'N/A'),
                        ],
                        $record->id,
                        'Assign committee request / طلب تعيين لجنة'
                    );

                    if (!($result['success'] ?? false)) {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (($result['requires_approval'] ?? false) === true) {
                        Notification::make()
                            ->title('Request Submitted / تم تقديم الطلب')
                            ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                        return;
                    }

                    $judges = CommitteeJudge::where('committee_id', $data['committees'])
                        ->pluck('judge_id');

                    foreach ($judges as $judge) {
                        JudgeProject::updateOrCreate(
                            ['project_id' => $record->id, 'judge_id' => $judge],
                            ['judge_id' => $judge]
                        );
                    }

                    Notification::make()
                        ->title('Committee Assigned / تم تعيين اللجنة')
                        ->body('Committee has been assigned successfully. / تم تعيين اللجنة بنجاح.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !$this->record->isArchived() && auth()->user()->can('update Project')),

            Action::make('archive')
                ->label('Archive / أرشفة')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Archive Project / أرشفة المشروع')
                ->modalDescription('Are you sure you want to archive this project? This action will be submitted for approval. / هل أنت متأكد من أرشفة هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->authorize(fn () => ProjectResource::canArchive($this->record))
                ->visible(fn () => !$this->record->isArchived())
                ->action(function (Project $record) {
                    $approvalService = new ProjectApprovalService();
                    $result = $approvalService->processAction(
                        'archive',
                        ['is_archived' => true, 'project_id' => $record->id, 'title' => $record->form_submissions['project_name'] ?? 'N/A'],
                        $record->id,
                        'Archive project request / طلب أرشفة مشروع'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your archive request has been submitted for approval. You will be notified once approved. / تم تقديم طلب الأرشفة للموافقة. سيتم إشعارك عند الموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                        } else {
                            $record->archive();
                            \Filament\Notifications\Notification::make()
                                ->title('Project Archived / تم أرشفة المشروع')
                                ->body('The project has been archived successfully. / تم أرشفة المشروع بنجاح.')
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
                }),

            Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Project / استعادة المشروع')
                ->modalDescription('Are you sure you want to restore this project? This action will be submitted for approval. / هل أنت متأكد من استعادة هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->authorize(fn () => ProjectResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function (Project $record) {
                    $approvalService = new ProjectApprovalService();

                    // Restore is governed by either a dedicated restore workflow OR the existing archive workflow.
                    $requiresApproval = $approvalService->hasWorkflowForAction('restore') || $approvalService->hasWorkflowForAction('archive');

                    if ($requiresApproval) {
                        $result = $approvalService->processAction(
                            'restore',
                            [
                                'is_archived' => false,
                                'project_id' => $record->id,
                                'title' => $record->form_submissions['project_name'] ?? 'N/A',
                            ],
                            $record->id,
                            'Restore project request / طلب استعادة مشروع'
                        );

                        if (!($result['success'] ?? false)) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Request Submitted / تم تقديم الطلب')
                            ->body('Your restore request has been submitted for approval. / تم تقديم طلب الاستعادة للموافقة.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                        return;
                    }

                    $record->restore();
                    Notification::make()
                        ->title('Project Restored / تم استعادة المشروع')
                        ->body('The project has been restored successfully. / تم استعادة المشروع بنجاح.')
                        ->success()
                        ->send();

                    $this->redirect(ProjectResource::getUrl('index'));
                }),

            Actions\Action::make('delete')
                ->label('Delete / حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Project / حذف المشروع')
                ->modalDescription('Are you sure you want to delete this project? This action will be submitted for approval. / هل أنت متأكد من حذف هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->authorize(fn () => ProjectResource::canDelete($this->record))
                ->visible(fn () => auth()->user()->can('delete Project'))
                ->action(function (Project $record) {
                    $approvalService = new ProjectApprovalService();
                    $result = $approvalService->processAction(
                        'delete',
                        ['project_id' => $record->id],
                        $record->id,
                        'Project deletion request / طلب حذف مشروع'
                    );

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
                                ->title('Project Deleted / تم حذف المشروع')
                                ->body('The project has been deleted successfully. / تم حذف المشروع بنجاح.')
                                ->success()
                                ->send();

                            $this->redirect(ProjectResource::getUrl('index'));
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Error / خطأ')
                            ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Project::details());
    }

    private function getFormSubmissionsArray(): array
    {
        $submissions = $this->record->form_submissions;

        if ($submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
            return $submissions->toArray();
        }

        if (is_array($submissions)) {
            return $submissions;
        }

        if (is_string($submissions)) {
            return json_decode($submissions, true) ?? [];
        }

        return [];
    }

    private function hasAiEvaluationPrerequisites(): bool
    {
        $config = \App\Models\FormAiScoringConfig::where('form_id', $this->record->form_id)->first();
        if (!$config) {
            return false;
        }

        $criteria = $config->activeAssessmentCriteria()->with('formFields')->get();
        if ($criteria->isEmpty()) {
            return false;
        }

        foreach ($criteria as $criterion) {
            if ($criterion->formFields->isEmpty()) {
                return false;
            }
        }

        return true;
    }
}
