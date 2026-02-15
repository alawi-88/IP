<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Exports\ProjectExporter;
use App\Filament\Imports\DynamicProjectImporter;
use App\Filament\Resources\ProjectResource;
use App\Models\CommitteeJudge;
use App\Models\Competition;
use App\Models\Form;
use App\Models\JudgeProject;
use App\Models\Project;
use App\Models\SubTrack;
use App\Models\Track;
use App\Services\ProjectApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Support\Collection;


class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        // Set default query to show all projects (active + archived)
        $table->query(Project::byCompetition()->submission()->with(['form', 'application.participant']));

        return $table
            ->columns(Project::columns())
            ->headerActions([
                ImportAction::make()
                    ->importer(DynamicProjectImporter::class)
                    ->label('Import Projects (Dynamic Forms)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(function () {
                        // Check permission first
                        if (!(auth()->user()?->can('create Project') ?? false)) {
                            return false;
                        }

                        // Get current competition from session
                        $currentCompetitionId = session('current_competition_id');

                        if (!$currentCompetitionId) {
                            return false;
                        }

                        // Get the competition
                        $competition = Competition::where('id', $currentCompetitionId)
                            ->published()
                            ->active()
                            ->first();

                        if (!$competition) {
                            return false;
                        }

                        // Check if current stage is project-submission
                        $currentStage = $competition->currentStage();
                        if (!$currentStage || $currentStage->slug !== 'project-submission') {
                            return false;
                        }

                        // Check if there is a project form linked to the competition
                        $hasProjectForm = Form::where('competition_id', $currentCompetitionId)
                            ->projectType()
                            ->published()
                            ->active()
                            ->exists();

                        return $hasProjectForm;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('assign to judges')
                    ->hiddenLabel()
                    ->color('primary')
                    ->icon('heroicon-o-users')
                    ->form(fn(Project $record) => $record->assignToJudgesForm($record))
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
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (($result['requires_approval'] ?? false) === true) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $record->assignToJudges($data['judges']);
                        \Filament\Notifications\Notification::make()
                            ->title('Judges Assigned / تم تعيين الحكام')
                            ->body('Judges have been assigned successfully. / تم تعيين الحكام بنجاح.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Project') ?? false),

                Tables\Actions\Action::make('assign to committee')
                    ->hiddenLabel()
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
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (($result['requires_approval'] ?? false) === true) {
                            \Filament\Notifications\Notification::make()
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

                        \Filament\Notifications\Notification::make()
                            ->title('Committee Assigned / تم تعيين اللجنة')
                            ->body('Committee has been assigned successfully. / تم تعيين اللجنة بنجاح.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Project') ?? false),

                Tables\Actions\Action::make('update status')
                    ->hiddenLabel()
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
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (($result['requires_approval'] ?? false) === true) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $record->setStatusAs($data['status']);
                        \Filament\Notifications\Notification::make()
                            ->title('Status Updated / تم تحديث الحالة')
                            ->body('Project status updated successfully. / تم تحديث حالة المشروع بنجاح.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Project') ?? false),

                Tables\Actions\Action::make('update evaluation status')
                    ->hiddenLabel()
                    ->color('primary')
                    ->icon('heroicon-o-percent-badge')
                    ->form(Project::updateEvaluationStatusForm())
                    ->requiresConfirmation()
                    ->modalHeading('Update Evaluation Status / تحديث حالة التقييم')
                    ->modalDescription('Are you sure you want to update this project evaluation status? This action will be submitted for approval. / هل أنت متأكد من تحديث حالة تقييم هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->action(function (array $data, Project $record) {
                        $approvalService = new ProjectApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            [
                                'evaluation_status' => (bool) $data['evaluation_status'],
                                'project_id' => $record->id,
                                'project_name' => data_get($record->form_submissions, 'project_name', 'N/A'),
                            ],
                            $record->id,
                            'Update evaluation status request / طلب تحديث حالة التقييم'
                        );

                        if (!($result['success'] ?? false)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (($result['requires_approval'] ?? false) === true) {
                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $record->setEvaluationStatusAs((bool) $data['evaluation_status']);
                        \Filament\Notifications\Notification::make()
                            ->title('Evaluation Status Updated / تم تحديث حالة التقييم')
                            ->body('Project evaluation status updated successfully. / تم تحديث حالة التقييم بنجاح.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Project') ?? false),

                Tables\Actions\Action::make('archive')
                    ->label(__('project_archive.archive_project'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('project_archive.archive_modal_heading'))
                    ->modalDescription('Are you sure you want to archive this project? This action will be submitted for approval. / هل أنت متأكد من أرشفة هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->modalSubmitActionLabel(__('project_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $approvalService = new ProjectApprovalService();
                        $result = $approvalService->processAction(
                            'archive',
                            ['is_archived' => true, 'project_id' => $record->id, 'title' => $record->form_submissions['project_name']],
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
                                    ->title(__('project_archive.project_archived'))
                                    ->body(__('project_archive.successfully_archived'))
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
                    ->visible(fn($record) => !$record->isArchived() && ProjectResource::canArchive($record)),

                Tables\Actions\Action::make('restore')
                    ->label(__('project_archive.restore_project'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('project_archive.restore_modal_heading'))
                    ->modalDescription(__('project_archive.restore_modal_description') . ' This action will be submitted for approval when an archive policy exists. / سيتم تقديم هذا الإجراء للموافقة عند وجود سياسة للأرشفة.')
                    ->modalSubmitActionLabel(__('project_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        $approvalService = new ProjectApprovalService();
                        $requiresApproval = $approvalService->hasWorkflowForAction('restore') || $approvalService->hasWorkflowForAction('archive');

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'restore',
                                [
                                    'is_archived' => false,
                                    'project_id' => $record->id,
                                    'title' => data_get($record->form_submissions, 'project_name', 'N/A'),
                                ],
                                $record->id,
                                'Restore project request / طلب استعادة مشروع'
                            );

                            if (!($result['success'] ?? false)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error / خطأ')
                                    ->body($result['message'] ?? 'Failed to submit request. / فشل في تقديم الطلب.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Request Submitted / تم تقديم الطلب')
                                ->body('Your restore request has been submitted for approval. / تم تقديم طلب الاستعادة للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title(__('project_archive.project_restored'))
                            ->body(__('project_archive.successfully_restored'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => $record->isArchived() && ProjectResource::canRestore($record)),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Project') ?? false),

                Tables\Actions\Action::make('delete')
                    ->label('Delete / حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Project / حذف المشروع')
                    ->modalDescription('Are you sure you want to delete this project? This action will be submitted for approval. / هل أنت متأكد من حذف هذا المشروع؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->authorize(fn ($record) => ProjectResource::canDelete($record))
                    ->visible(fn () => auth()->user()?->can('delete Project') ?? false)
                    ->action(function (Project $record) {
                        $approvalService = new ProjectApprovalService();
                        $result = $approvalService->processAction(
                            'delete',
                            ['project_id' => $record->id, 'title' => $record->form_submissions['project_name']],
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
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error / خطأ')
                                ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('project_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('project_archive.archive_bulk_heading'))
                        ->modalDescription('Are you sure you want to archive the selected projects? This action will be submitted for approval. / هل أنت متأكد من أرشفة المشاريع المحددة؟ سيتم تقديم هذا الإجراء للموافقة.')
                        ->modalSubmitActionLabel(__('project_archive.archive_bulk_confirm'))
                        ->action(function ($records) {
                            $approvalService = new ProjectApprovalService();
                            $count = 0;
                            $alreadyArchived = 0;
                            $pendingApproval = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $result = $approvalService->processAction(
                                        'archive',
                                        ['is_archived' => true, 'project_id' => $record->id, 'title' => $record->form_submissions['project_name']],
                                        $record->id,
                                        'Bulk archive project request / طلب أرشفة جماعية للمشاريع'
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
                                    ->title(__('project_archive.project_archived'))
                                    ->body(__('project_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} archive requests have been submitted for approval. / تم تقديم {$pendingApproval} طلب أرشفة للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('project_archive.warning'))
                                    ->body(__('project_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('project_archive.no_action_taken'))
                                    ->body(__('project_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Project'))
                        ->authorize(fn () => auth()->user()?->can('archive Project')),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('project_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('project_archive.restore_bulk_heading'))
                        ->modalDescription(__('project_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('project_archive.restore_bulk_confirm'))
                        ->action(function ($records) {
                            $approvalService = new ProjectApprovalService();
                            $requiresApproval = $approvalService->hasWorkflowForAction('restore') || $approvalService->hasWorkflowForAction('archive');

                            $count = 0;
                            $alreadyActive = 0;
                            $pendingApproval = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $alreadyActive++;
                                    continue;
                                }

                                if ($requiresApproval) {
                                    $result = $approvalService->processAction(
                                        'restore',
                                        [
                                            'is_archived' => false,
                                            'project_id' => $record->id,
                                            'title' => data_get($record->form_submissions, 'project_name', 'N/A'),
                                        ],
                                        $record->id,
                                        'Bulk restore project request / طلب استعادة جماعية للمشاريع'
                                    );

                                    if (!($result['success'] ?? false)) {
                                        $failed++;
                                        continue;
                                    }

                                    $pendingApproval++;
                                    continue;
                                }

                                $record->restore();
                                $count++;
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('project_archive.project_restored'))
                                    ->body(__('project_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} restore request(s) have been submitted for approval. / تم تقديم {$pendingApproval} طلب استعادة للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failed > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failed} request(s) failed. / فشل {$failed} طلب.")
                                    ->warning()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('project_archive.warning'))
                                    ->body(__('project_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('project_archive.no_action_taken'))
                                    ->body(__('project_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Project'))
                        ->authorize(fn () => auth()->user()?->can('restore Project')),

                    Tables\Actions\BulkAction::make('bulk-assign-to-judges')
                        ->label('Assign to Judges')
                        ->icon('heroicon-o-users')
                        ->color('primary')
                        ->form(fn ($records) => (new Project())->assignToJudgesForm($records))
                        ->requiresConfirmation()
                        ->action(function (array $data, Collection $records) {
                            $approvalService = new ProjectApprovalService();
                            $updatedCount = 0;
                            $pendingApprovalCount = 0;
                            $failedCount = 0;
                            $skippedArchived = 0;

                            foreach ($records as $record) {
                                /** @var Project $record */
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                $result = $approvalService->processAction(
                                    'update',
                                    ['judge_ids' => $data['judges']],
                                    $record->id,
                                    'Bulk assign judges request / طلب تعيين حكام جماعي'
                                );

                                if (!($result['success'] ?? false)) {
                                    $failedCount++;
                                    continue;
                                }

                                if (($result['requires_approval'] ?? false) === true) {
                                    $pendingApprovalCount++;
                                    continue;
                                }

                                $record->assignToJudges($data['judges']);
                                $updatedCount++;
                            }

                            if ($updatedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Judges Assigned / تم تعيين الحكام')
                                    ->body("{$updatedCount} project(s) have been assigned to judges successfully. / تم تعيين {$updatedCount} مشروع للحكام بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApprovalCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApprovalCount} judge assignment request(s) submitted for approval. / تم تقديم {$pendingApprovalCount} طلب تعيين حكام للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failedCount} request(s) failed. / فشل {$failedCount} طلب.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Projects Skipped / تم تخطي المشاريع المؤرشفة')
                                    ->body("{$skippedArchived} archived project(s) were skipped. Archived projects cannot be updated. / تم تخطي {$skippedArchived} مشروع مؤرشف. لا يمكن تحديث المشاريع المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Project') ?? false),

                    Tables\Actions\BulkAction::make('bulk-assign-to-committee')
                        ->label('Assign to Committee')
                        ->icon('heroicon-o-user-circle')
                        ->color('primary')
                        ->form(fn ($records) => Project::assignToCommitteeForm($records))
                        ->requiresConfirmation()
                        ->action(function (array $data, Collection $records) {
                            $approvalService = new ProjectApprovalService();
                            $updatedCount = 0;
                            $pendingApprovalCount = 0;
                            $failedCount = 0;
                            $skippedArchived = 0;

                            $judges = CommitteeJudge::where('committee_id', $data['committees'])->pluck('judge_id');

                            foreach ($records as $record) {
                                /** @var Project $record */
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                $result = $approvalService->processAction(
                                    'update',
                                    ['committee_id' => $data['committees']],
                                    $record->id,
                                    'Bulk assign committee request / طلب تعيين لجنة جماعي'
                                );

                                if (!($result['success'] ?? false)) {
                                    $failedCount++;
                                    continue;
                                }

                                if (($result['requires_approval'] ?? false) === true) {
                                    $pendingApprovalCount++;
                                    continue;
                                }

                                foreach ($judges as $judge) {
                                    JudgeProject::updateOrCreate(
                                        ['project_id' => $record->id, 'judge_id' => $judge],
                                        ['judge_id' => $judge]
                                    );
                                }
                                $updatedCount++;
                            }

                            if ($updatedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Committee Assigned / تم تعيين اللجنة')
                                    ->body("{$updatedCount} project(s) have been assigned to committee successfully. / تم تعيين {$updatedCount} مشروع للجنة بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApprovalCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApprovalCount} committee assignment request(s) submitted for approval. / تم تقديم {$pendingApprovalCount} طلب تعيين لجنة للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failedCount} request(s) failed. / فشل {$failedCount} طلب.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Projects Skipped / تم تخطي المشاريع المؤرشفة')
                                    ->body("{$skippedArchived} archived project(s) were skipped. Archived projects cannot be updated. / تم تخطي {$skippedArchived} مشروع مؤرشف. لا يمكن تحديث المشاريع المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Project') ?? false),

                    Tables\Actions\BulkAction::make('bulk-update-status')
                        ->label('Update Status')
                        ->icon('heroicon-o-pencil')
                        ->color('primary')
                        ->form(fn ($records) => (new Project())->updateStatusForm($records))
                        ->requiresConfirmation()
                        ->action(function (array $data, Collection $records) {
                            $approvalService = new ProjectApprovalService();
                            $updatedCount = 0;
                            $pendingApprovalCount = 0;
                            $failedCount = 0;
                            $skippedArchived = 0;

                            foreach ($records as $record) {
                                /** @var Project $record */
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                $result = $approvalService->processAction(
                                    'update',
                                    ['status' => $data['status']],
                                    $record->id,
                                    'Bulk update status request / طلب تحديث حالة جماعي'
                                );

                                if (!($result['success'] ?? false)) {
                                    $failedCount++;
                                    continue;
                                }

                                if (($result['requires_approval'] ?? false) === true) {
                                    $pendingApprovalCount++;
                                    continue;
                                }

                                $record->setStatusAs($data['status']);
                                $updatedCount++;
                            }

                            if ($updatedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Status Updated / تم تحديث الحالة')
                                    ->body("{$updatedCount} project(s) status has been updated successfully. / تم تحديث حالة {$updatedCount} مشروع بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApprovalCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApprovalCount} status update request(s) submitted for approval. / تم تقديم {$pendingApprovalCount} طلب تحديث حالة للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failedCount} request(s) failed. / فشل {$failedCount} طلب.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Projects Skipped / تم تخطي المشاريع المؤرشفة')
                                    ->body("{$skippedArchived} archived project(s) were skipped. Archived projects cannot be updated. / تم تخطي {$skippedArchived} مشروع مؤرشف. لا يمكن تحديث المشاريع المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Project') ?? false),

                    Tables\Actions\BulkAction::make('bulk-update-evaluation-status')
                        ->label('Update Evaluation Status')
                        ->icon('heroicon-o-percent-badge')
                        ->color('primary')
                        ->form(fn ($records) => Project::updateEvaluationStatusForm($records))
                        ->requiresConfirmation()
                        ->action(function (array $data, Collection $records) {
                            $approvalService = new ProjectApprovalService();
                            $updatedCount = 0;
                            $pendingApprovalCount = 0;
                            $failedCount = 0;
                            $skippedArchived = 0;

                            foreach ($records as $record) {
                                /** @var Project $record */
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                $result = $approvalService->processAction(
                                    'update',
                                    ['evaluation_status' => (bool) $data['evaluation_status']],
                                    $record->id,
                                    'Bulk update evaluation status request / طلب تحديث حالة التقييم جماعي'
                                );

                                if (!($result['success'] ?? false)) {
                                    $failedCount++;
                                    continue;
                                }

                                if (($result['requires_approval'] ?? false) === true) {
                                    $pendingApprovalCount++;
                                    continue;
                                }

                                $record->setEvaluationStatusAs((bool) $data['evaluation_status']);
                                $updatedCount++;
                            }

                            if ($updatedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Evaluation Status Updated / تم تحديث حالة التقييم')
                                    ->body("{$updatedCount} project(s) evaluation status has been updated successfully. / تم تحديث حالة التقييم لـ {$updatedCount} مشروع بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApprovalCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApprovalCount} evaluation status update request(s) submitted for approval. / تم تقديم {$pendingApprovalCount} طلب تحديث حالة التقييم للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failedCount} request(s) failed. / فشل {$failedCount} طلب.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Projects Skipped / تم تخطي المشاريع المؤرشفة')
                                    ->body("{$skippedArchived} archived project(s) were skipped. Archived projects cannot be updated. / تم تخطي {$skippedArchived} مشروع مؤرشف. لا يمكن تحديث المشاريع المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('update Project') ?? false),

                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete Selected / حذف المحدد')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Projects / حذف المشاريع المحددة')
                        ->modalDescription('Are you sure you want to delete the selected projects? This action will be submitted for approval. / هل أنت متأكد من حذف المشاريع المحددة؟ سيتم تقديم هذا الإجراء للموافقة.')
                        ->action(function (Collection $records) {
                            $approvalService = new ProjectApprovalService();
                            $deletedCount = 0;
                            $pendingApprovalCount = 0;
                            $failedCount = 0;

                            foreach ($records as $record) {
                                /** @var Project $record */
                                $result = $approvalService->processAction(
                                    'delete',
                                    [
                                        'project_id' => $record->id,
                                        'title' => data_get($record->form_submissions, 'project_name', 'N/A'),
                                    ],
                                    $record->id,
                                    'Bulk project deletion request / طلب حذف جماعي للمشاريع'
                                );

                                if (!($result['success'] ?? false)) {
                                    $failedCount++;
                                    continue;
                                }

                                if (($result['requires_approval'] ?? false) === true) {
                                    $pendingApprovalCount++;
                                    continue;
                                }

                                // No approval workflow exists; delete immediately.
                                $record->delete();
                                $deletedCount++;
                            }

                            if ($deletedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Projects Deleted / تم حذف المشاريع')
                                    ->body("{$deletedCount} project(s) deleted successfully. / تم حذف {$deletedCount} مشروع بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApprovalCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApprovalCount} deletion request(s) submitted for approval. / تم تقديم {$pendingApprovalCount} طلب حذف للموافقة.")
                                    ->success()
                                    ->send();

                                $this->redirect(route('filament.admin.resources.my-requests.index'));
                            }

                            if ($failedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Requests Failed / فشل بعض الطلبات')
                                    ->body("{$failedCount} deletion request(s) failed. / فشل {$failedCount} طلب حذف.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('delete Project') ?? false)
                        ->authorize(fn () => auth()->user()?->can('delete Project') ?? false),
                ]),

                ExportBulkAction::make()
                    ->exporter(ProjectExporter::class)
                    ->columnMapping(false)
                    ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['comments', 'judges']))
                    ->fileName(fn (\Filament\Actions\Exports\Models\Export $export) => 'Projects_' . now()->format('Y-m-d_His'))
                    ->after(function () {
                        $user = auth()->user();
                        if (!$user) {
                            return;
                        }
                        $export = \Filament\Actions\Exports\Models\Export::where('user_id', $user->getAuthIdentifier())
                            ->latest()
                            ->first();
                        if (!$export) {
                            return;
                        }
                        activity('Export')
                            ->performedOn($export)
                            ->causedBy($user)
                            ->event('exported')
                            ->withProperties([
                                'resource' => 'Projects',
                                'file_name' => $export->file_name,
                                'total_rows' => $export->total_rows,
                                'export_timestamp' => now()->toIso8601String(),
                                'criteria' => [
                                    'competition_id' => session('current_competition_id'),
                                ],
                            ])
                            ->log($user->name . ' exported projects');
                    }),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('track_id')
                    ->label('Track')
                    ->placeholder('All Tracks')
                    ->options(fn() => Track::pluck('name', 'id')->toArray())
                    ->relationship('team.track', 'name'),

                Tables\Filters\SelectFilter::make('SubTrack_id')
                    ->label('Challenge')
                    ->placeholder('All SubTrack')
                    ->options(fn() => SubTrack::pluck('name', 'id')->toArray())
                    ->relationship('team.SubTrack', 'name'),

                 Tables\Filters\SelectFilter::make('evaluation_status')
                     ->label('Evaluation Status')
                     ->placeholder('All Evaluation Status')
                     ->options([
                         1 => 'Evaluated',
                         0 => 'Not Evaluated',
                     ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All Status')
                    ->options([
                        'pending' => 'Pending',
                        'qualified' => 'Qualified',
                        'not_qualified' => 'Not Qualified',
                        'winner' => 'Winner'
                    ]),
            ]);
    }

    public function getTabs(): array
    {
        $baseQuery = Project::byCompetition()->submission();

        $tabs = [
            'all' => \Filament\Resources\Components\Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => \Filament\Resources\Components\Tab::make(__('project_archive.active_projects'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive Project') || auth()->user()?->can('restore Project')) {
            $tabs['archived'] = \Filament\Resources\Components\Tab::make(__('project_archive.archived_projects'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}

