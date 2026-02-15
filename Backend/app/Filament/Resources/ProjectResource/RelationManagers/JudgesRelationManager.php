<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Exports\JudgeRelationExporter;
use App\Filament\Traits\ManageableRelation;
use App\Models\Judge;
use App\Models\JudgeProject;
use App\Models\Project;
use App\Models\ProjectEvaluation;
use App\Services\ProjectApprovalService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;

class JudgesRelationManager extends RelationManager
{
    use ManageableRelation;

    protected static string $relationship = 'judges';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(array_merge(Judge::columns(), [
                Tables\Columns\TextColumn::make('evaluation_score')
                    ->label('Evaluation Score')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $judgeProjectId = $record->pivot?->id;

                        // Try to get the form evaluation score record for this judge project (only non-archived and not excluded)
                        $formScore = \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                            ->where('is_archived', false)
                            ->where('exclude_from_calculation', false)
                            ->first();

                        return $formScore
                            ? round($formScore->evaluation_score, 2) . '%'
                            : '—';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(function ($record) {
                        return $record->pivot?->created_at->format('Y-m-d H:i');
                    })
                    ->label('Created At')
                    ->searchable(
                        query: function (Builder $query, string $search) {
                            return $query->where('judge_projects.created_at', 'like', "%{$search}%");
                        },
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('judge_projects.created_at', $direction);
                    }),

                Tables\Columns\IconColumn::make('evaluation_included')
                    ->label('Evaluation Included')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        $judgeProjectId = $record->pivot?->id;
                        $formScore = \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                            ->where('is_archived', false)
                            ->first();
                        
                        return $formScore ? !$formScore->exclude_from_calculation : false;
                    })
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(bool $state) => $state ? 'success' : 'danger')
                    ->tooltip(function ($record) {
                        $judgeProjectId = $record->pivot?->id;
                        $formScore = \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                            ->where('is_archived', false)
                            ->first();
                        
                        if (!$formScore) {
                            return 'No evaluation found';
                        }
                        
                        return $formScore->exclude_from_calculation ? 'Excluded from average calculation' : 'Included in average calculation';
                    }),
            ]))
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn($record) => 'Evaluations for [' . ($record->name) . ']')
                    ->modalContent(function ($record) {
                        $judgeProjectId = $record->pivot?->id;
                        $formId = \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                            ->where('is_archived', false)
                            ->value('form_id');

                        return view('filament.modals.evaluations', [
                            'evaluations' => ProjectEvaluation::where('judge_project_id', $judgeProjectId)
                                ->where('form_id', $formId)
                                ->where('is_archived', false)
                                ->get(),
                            'formScore' => \App\Models\FormEvaluationScore::where('judge_project_id', $judgeProjectId)
                                ->where('form_id', $formId)
                                ->where('is_archived', false)
                                ->first(),
                            'form' => \App\Models\Form::find($formId),
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Tables\Actions\DeleteAction::make()
                    ->action(function (Judge $record) {
                        $project = $this->getOwnerRecord();
                        if (!$project || $project->isArchived()) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body('Cannot update an archived project. / لا يمكن تحديث مشروع مؤرشف.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $approvalService = new ProjectApprovalService();
                        $requiresApproval = $approvalService->hasWorkflowForAction('update');

                        // Compute the new judge list (current minus this judge)
                        $currentJudgeIds = $project->judges()->pluck('judges.id')->toArray();
                        $newJudgeIds = array_values(array_diff($currentJudgeIds, [$record->id]));

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'update',
                                [
                                    'judge_ids' => $newJudgeIds,
                                    'project_id' => $project->id,
                                    'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                                ],
                                $project->id,
                                'Remove judge request / طلب إزالة حكم'
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
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        // No update workflow configured; apply immediately.
                        $project->assignToJudges($newJudgeIds);
                        $project->updateScore();
                    })->visible(fn () => auth()->user()->can('update Project') && !$this->getOwnerRecord()?->isArchived()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->action(function (Collection $records) {
                        $project = $this->getOwnerRecord();
                        if (!$project || $project->isArchived()) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body('Cannot update an archived project. / لا يمكن تحديث مشروع مؤرشف.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $approvalService = new ProjectApprovalService();
                        $requiresApproval = $approvalService->hasWorkflowForAction('update');

                        $selectedJudgeIds = $records->pluck('id')->filter()->values()->all();
                        $currentJudgeIds = $project->judges()->pluck('judges.id')->toArray();
                        $newJudgeIds = array_values(array_diff($currentJudgeIds, $selectedJudgeIds));

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'update',
                                [
                                    'judge_ids' => $newJudgeIds,
                                    'project_id' => $project->id,
                                    'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                                ],
                                $project->id,
                                'Bulk remove judges request / طلب إزالة حكام جماعي'
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
                                ->body('Your request has been submitted for approval. / تم تقديم طلبك للموافقة.')
                                ->success()
                                ->send();

                            $this->redirect(route('filament.admin.resources.my-requests.index'));
                            return;
                        }

                        $project->assignToJudges($newJudgeIds);
                        $project->updateScore();
                    })->visible(fn () => auth()->user()->can('update Project') && !$this->getOwnerRecord()?->isArchived()),
                ]),
                ExportBulkAction::make()
                    ->exporter(JudgeRelationExporter::class)
                    ->columnMapping(false)
                    ->fileName('Project_Judges_List_' . now()->format('Y-m-d')),
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }
}
