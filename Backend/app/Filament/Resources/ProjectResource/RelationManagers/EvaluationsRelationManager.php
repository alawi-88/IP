<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Exports\EvaluationRelationExporter;
use App\Filament\Traits\ManageableRelation;
use App\Models\FormEvaluationScore;
use App\Models\Judge;
use App\Models\JudgeProject;
use App\Models\ProjectEvaluation;
use App\Models\Track;
use App\Services\ProjectApprovalService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class EvaluationsRelationManager extends RelationManager
{
    use ManageableRelation;

    protected static string $relationship = 'evaluations';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FormEvaluationScore::query()
                    ->selectRaw("
                            form_evaluation_scores.id,
                            form_evaluation_scores.form_id,
                            JSON_UNQUOTE(JSON_EXTRACT(forms.name, '$.\"".app()->getLocale()."\"')) AS form_name,
                            form_evaluation_scores.judge_project_id,
                            form_evaluation_scores.evaluation_score,
                            form_evaluation_scores.created_at,
                            form_evaluation_scores.exclude_from_calculation,
                            JSON_UNQUOTE(JSON_EXTRACT(judges.name, '$.\"".app()->getLocale()."\"')) AS judge_name
                        ")
                    ->join('judge_projects', 'judge_projects.id', '=', 'form_evaluation_scores.judge_project_id')
                    ->join('forms', 'forms.id', '=', 'form_evaluation_scores.form_id')
                    ->join('judges', 'judges.id', '=', 'judge_projects.judge_id')
                    ->where('judge_projects.project_id', $this->getOwnerRecord()->id)
                    ->where('form_evaluation_scores.is_archived', false)
            )
            ->recordTitleAttribute('id')
            ->columns(array_merge(ProjectEvaluation::columns(), [
                Tables\Columns\IconColumn::make('exclude_from_calculation')
                    ->label('Included in Average')
                    ->boolean()
                    ->getStateUsing(fn($record) => !$record->exclude_from_calculation)
                    ->icon(fn(bool $state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn(bool $state) => $state ? 'success' : 'danger')
                    ->tooltip(fn($record) => $record->exclude_from_calculation ? 'Excluded from average calculation' : 'Included in average calculation'),
            ]))
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn($record) => 'Evaluations for [' . ($record->judgeProject?->judge->name) . ']')
                    ->modalContent(fn($record) => view('filament.modals.evaluations', [
                        'evaluations' => ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->where('is_archived', false)
                            ->get(),
                        'formScore' => \App\Models\FormEvaluationScore::where('judge_project_id', $record->judge_project_id)
                            ->where('form_id', $record->form_id)
                            ->where('is_archived', false)
                            ->first(),
                        'form' => \App\Models\Form::find($record->form_id),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Tables\Actions\DeleteAction::make()
                ->action(function (FormEvaluationScore $record) {
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

                    if ($requiresApproval) {
                        $result = $approvalService->processAction(
                            'update',
                            [
                                'delete_evaluations' => [
                                    [
                                        'form_evaluation_score_id' => $record->id,
                                        'judge_project_id' => $record->judge_project_id,
                                        'form_id' => $record->form_id,
                                    ],
                                ],
                                'project_id' => $project->id,
                                'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                            ],
                            $project->id,
                            'Delete evaluation request / طلب حذف التقييم'
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

                    // No update workflow configured; apply immediately (archive evaluation).
                    $record->archive();
                    $record->judgeProject?->update(['evaluation_score' => 0]);
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

                        $payloadItems = $records->map(function ($record) {
                            return [
                                'form_evaluation_score_id' => $record->id,
                                'judge_project_id' => $record->judge_project_id,
                                'form_id' => $record->form_id,
                            ];
                        })->values()->all();

                        if ($requiresApproval) {
                            $result = $approvalService->processAction(
                                'update',
                                [
                                    'delete_evaluations' => $payloadItems,
                                    'project_id' => $project->id,
                                    'project_name' => data_get($project->form_submissions, 'project_name', 'N/A'),
                                ],
                                $project->id,
                                'Bulk delete evaluations request / طلب حذف تقييمات جماعي'
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
                        foreach ($records as $record) {
                            $record->archive();
                            $record->judgeProject?->update(['evaluation_score' => 0]);
                        }
                    })->visible(fn () => auth()->user()->can('update Project') && !$this->getOwnerRecord()?->isArchived()),
                ]),
                ExportBulkAction::make()
                    ->exporter(EvaluationRelationExporter::class)
                    ->columnMapping(false)
                    ->fileName('Project_Evaluations_List_' . now()->format('Y-m-d')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('judge_id')
                    ->label('Judge')
                    ->options(fn() => Judge::pluck('name', 'id')),
                
                Tables\Filters\TernaryFilter::make('exclude_from_calculation')
                    ->label('Include in Average')
                    ->placeholder('All evaluations')
                    ->trueLabel('Included only')
                    ->falseLabel('Excluded only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('form_evaluation_scores.exclude_from_calculation', false),
                        false: fn (Builder $query) => $query->where('form_evaluation_scores.exclude_from_calculation', true),
                    ),
            ]);
    }
}
