<?php

namespace App\Filament\Resources\ProgramApplicationResource\Pages;

use App\Filament\Exports\ProgramApplicationExporter;
use App\Filament\Imports\ProgramApplicationImporter;
use App\Filament\Imports\DynamicProgramApplicationImporter;
use App\Filament\Resources\ProgramApplicationResource;
use App\Models\ProgramApplication;
use App\Services\ApplicationApprovalService;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Forms\Components\ViewField;

class ListProgramApplications extends ListRecords
{
    protected static string $resource = ProgramApplicationResource::class;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        // Build base query
        $baseQuery = ProgramApplication::query()->submission();

        // Filter by admin's assigned programs (unless super admin)
        if ($user && !$user->isSuperAdmin()) {
            // Get program IDs the user has access to
            $programIds = \App\Models\UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();

            if (!empty($programIds)) {
                $baseQuery->whereIn('program_id', $programIds);
            } else {
                // If user has no assigned programs, show nothing
                $baseQuery->whereRaw('1 = 0');
            }
        } else {
            // Super admin sees all applications (use byProgram if needed)
            $baseQuery = ProgramApplication::byProgram()->submission();
        }

        // Set default query to show active applications
        $table->query($baseQuery);

        return $table
            ->columns(ProgramApplication::columns())
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Import Applications (Dynamic Forms)')
                    ->importer(DynamicProgramApplicationImporter::class)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(function () {
                        if (!auth()->user()->can('create ProgramApplication')) {
                            return false;
                        }

                        // Check if current program exists, is open, and current stage is Registration
                        $currentProgramId = session('current_program_id');

                        if (!$currentProgramId) {
                            return false;
                        }

                        $currentProgram = \App\Models\Program::published()
                            ->active()
                            ->where('id', $currentProgramId)
                            ->whereHas('stages', function ($q) {
                                $q->where('slug', 'registration')
                                  ->where('ends_at', '>', now());
                            })
                            ->first();

                        if (!$currentProgram) {
                            return false;
                        }

                        // Check if current stage is Registration
                        $currentStage = $currentProgram->currentStage();
                        if (!$currentStage || $currentStage->slug !== 'registration') {
                            return false;
                        }

                        // Check if form exists for this program
                        $form = \App\Models\Form::where('program_id', $currentProgramId)
                            ->registrationType()
                            ->published()
                            ->active()
                            ->first();

                        return $form !== null;
                    }),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading(function ($record) {
                        return 'Approve Application / موافقة على التطبيق';
                    })
                    ->modalDescription(function ($record) {
                        $hasScoring = $record->hasScoringEnabled();
                        $criteria = $record->getAssessmentCriteria();
                        $hasCriteria = $criteria->isNotEmpty();
                        $hasExistingScores = $record->assessment_scores !== null && !empty($record->assessment_scores);

                        if ($hasScoring && $hasCriteria) {
                            if ($hasExistingScores) {
                                return 'This application already has scores assigned. You can review and modify them before approving. / هذا التطبيق لديه نقاط مخصصة بالفعل. يمكنك مراجعتها وتعديلها قبل الموافقة.';
                            }
                            return 'You must enter scores for each assessment criterion before approving. / يجب إدخال النقاط لكل معيار تقييم قبل الموافقة.';
                        }
                        return 'Are you sure you want to approve this application? This action will be submitted for approval. / هل أنت متأكد من الموافقة على هذا التطبيق؟ سيتم تقديم هذا الإجراء للموافقة.';
                    })
                    ->form(function ($record) {
                        $hasScoring = $record->hasScoringEnabled();
                        $criteria = $record->getAssessmentCriteria();
                        $hasCriteria = $criteria->isNotEmpty();
                        $hasExistingScores = $record->assessment_scores !== null && !empty($record->assessment_scores);

                        // If scoring is enabled, scoring form is REQUIRED
                        if ($hasScoring && $hasCriteria) {
                            // Form fields will be shown below
                        } else {
                            // No scoring required, return empty form
                            return [];
                        }

                        $formFields = [];

                        // Add required scoring section header with criteria summary
                        $criteriaSummary = $criteria->map(function ($criterion) {
                            return "• {$criterion->description} (Max: {$criterion->max_score})";
                        })->join("\n");

                        $formFields[] = Forms\Components\Section::make('Required Scoring / التقييم المطلوب')
                            ->description('You must enter scores for all assessment criteria before approving this application. / يجب إدخال النقاط لجميع معايير التقييم قبل الموافقة على هذا التطبيق.')
                            ->schema([
                                Forms\Components\Placeholder::make('criteria_summary')
                                    ->label('Assessment Criteria / معايير التقييم')
                                    ->content($criteriaSummary)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull();

                        // Calculate maximum total score
                        $maxTotalScore = 0;
                        foreach ($criteria as $criterion) {
                            $maxTotalScore += $criterion->max_score;
                        }

                        // Build scoring form fields for all criteria
                        foreach ($criteria as $criterion) {
                            $criterionId = (string) $criterion->id;
                            $existingScore = $hasExistingScores ? ($record->assessment_scores[$criterion->id] ?? 0) : null;

                            // Determine default score
                            $defaultScore = $existingScore ?? 0;

                            // Build helper text
                            $helperText = "Maximum score: {$criterion->max_score} / الحد الأقصى للنقاط: {$criterion->max_score}";

                            $formFields[] = Forms\Components\TextInput::make("scores.{$criterionId}")
                                ->label($criterion->description)
                                ->helperText($helperText)
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue($criterion->max_score)
                                ->default($defaultScore)
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, callable $get) use ($criteria, $maxTotalScore) {
                                    // Recalculate total whenever any score changes
                                    $total = 0;
                                    foreach ($criteria as $criterion) {
                                        $score = $get("scores.{$criterion->id}") ?? 0;
                                        $total += (int) $score;
                                    }
                                    // Display as "total / maxTotal"
                                    $totalDisplay = $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                                    $set('total_score_display', $totalDisplay);
                                })
                                ->rules([
                                    'required',
                                    'integer',
                                    'min:0',
                                    "max:{$criterion->max_score}",
                                ]);
                        }

                        // Add total score display (read-only, calculated, reactive) with maximum
                        $formFields[] = Forms\Components\TextInput::make('total_score_display')
                            ->label('Total Score / النقاط الإجمالية')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function (callable $get) use ($criteria, $maxTotalScore) {
                                // Calculate initial total
                                $total = 0;
                                foreach ($criteria as $criterion) {
                                    $score = $get("scores.{$criterion->id}") ?? 0;
                                    $total += (int) $score;
                                }
                                // Display as "total / maxTotal"
                                return $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                            })
                            ->reactive()
                            ->formatStateUsing(function ($state, callable $get) use ($criteria, $maxTotalScore) {
                                // Calculate total from current form state
                                $total = 0;
                                foreach ($criteria as $criterion) {
                                    $score = $get("scores.{$criterion->id}") ?? 0;
                                    $total += (int) $score;
                                }
                                // Display as "total / maxTotal"
                                return $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                            })
                            ->extraAttributes([
                                'class' => 'text-lg font-bold',
                            ])
                            ->columnSpanFull();

                        return $formFields;
                    })
                    ->visible(fn($record) => !$record->isArchived() && ($record->isPending() || $record->isRejected()))
                    ->action(function ($record, array $data) {
                        $hasScoring = $record->hasScoringEnabled();
                        $criteria = $record->getAssessmentCriteria();
                        $hasCriteria = $criteria->isNotEmpty();
                        $hasExistingScores = $record->assessment_scores !== null && !empty($record->assessment_scores);

                        // REQUIRED: If scoring is enabled, scores MUST be provided
                        if ($hasScoring && $hasCriteria) {
                            // Validate that scores are provided
                            if (!isset($data['scores']) || empty($data['scores'])) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'scores' => __('scoring.scores_required_for_approval'),
                                ]);
                            }

                            // Validate all criteria have scores
                            $missingScores = [];
                            $invalidScores = [];

                            foreach ($criteria as $criterion) {
                                $criterionId = (string) $criterion->id;

                                // Check if score is provided
                                if (!isset($data['scores'][$criterionId]) || $data['scores'][$criterionId] === null || $data['scores'][$criterionId] === '') {
                                    $missingScores[] = $criterion->description;
                                    continue;
                                }

                                // Validate score is numeric and within range
                                $score = $data['scores'][$criterionId];
                                if (!is_numeric($score)) {
                                    $invalidScores[] = $criterion->description . ' (must be a number)';
                                    continue;
                                }

                                $score = (int) $score;
                                if ($score < 0 || $score > $criterion->max_score) {
                                    $invalidScores[] = $criterion->description . " (must be between 0 and {$criterion->max_score})";
                                }
                            }

                            // Throw validation errors if any issues found
                            if (!empty($missingScores)) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'scores' => __('scoring.all_criteria_required', ['criteria' => implode(', ', $missingScores)]),
                                ]);
                            }

                            if (!empty($invalidScores)) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'scores' => __('scoring.invalid_scores', ['criteria' => implode(', ', $invalidScores)]),
                                ]);
                            }

                            // Convert scores array to format: criterion_id => score
                            $scores = [];
                            foreach ($data['scores'] as $criterionId => $score) {
                                $scores[(int) $criterionId] = (int) $score;
                            }

                            // Set scores and calculate total
                            $record->setAssessmentScores($scores);
                        }

                        // Process approval through approval service
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            ['status' => 'approved', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Approve application request / طلب موافقة على التطبيق'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                    ->body('Your approval request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                // Approve the application (only after successful scoring if required)
                                $record->approve();
                                \Filament\Notifications\Notification::make()
                                    ->title('Application Approved / تم الموافقة على التطبيق')
                                    ->body($record->hasScoringEnabled() && $record->total_score !== null
                                        ? "The application has been approved with a total score of {$record->total_score}. / تم الموافقة على التطبيق بإجمالي نقاط {$record->total_score}."
                                        : 'The application has been approved successfully. / تم الموافقة على التطبيق بنجاح.')
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
                    ->authorize(fn ($record) =>
                        auth()->user()?->can('update ProgramApplication') &&
                        ProgramApplicationResource::canView($record)
                    ),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Application / رفض التطبيق')
                    ->modalDescription('Are you sure you want to reject this application? This action will be submitted for approval. / هل أنت متأكد من رفض هذا التطبيق؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->visible(fn($record) => !$record->isArchived() && ($record->isPending() || $record->isApproved()))
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'update',
                            ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Reject application request / طلب رفض التطبيق'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                    ->body('Your rejection request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                $record->reject();
                                \Filament\Notifications\Notification::make()
                                    ->title('Application Rejected / تم رفض التطبيق')
                                    ->body('The application has been rejected successfully.')
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
                    ->authorize(fn ($record) =>
                        auth()->user()?->can('update ProgramApplication') &&
                        ProgramApplicationResource::canView($record)
                    ),

                Action::make('archive')
                    ->label(__('application_archive.archive_applications'))
                    ->color('warning')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->modalHeading(__('application_archive.archive_modal_heading'))
                    ->modalDescription(__('application_archive.archive_modal_description'))
                    ->modalSubmitActionLabel(__('application_archive.archive_modal_confirm'))
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'archive',
                            ['is_archived' => true, 'archived_at' => now(), 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Archive application request / طلب أرشفة التطبيق'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                    ->body('Your archive request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.applications_archived'))
                                    ->body(__('application_archive.successfully_archived'))
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
                    ->visible(fn($record) => !$record->isArchived() && ProgramApplicationResource::canArchive($record)),

                Action::make('restore')
                    ->label(__('application_archive.restore_applications'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__('application_archive.restore_modal_heading'))
                    ->modalDescription(__('application_archive.restore_modal_description'))
                    ->modalSubmitActionLabel(__('application_archive.restore_modal_confirm'))
                    ->action(function ($record) {
                        try {
                            $record->restore();
                            \Filament\Notifications\Notification::make()
                                ->title(__('application_archive.applications_restored'))
                                ->body(__('application_archive.successfully_restored'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('application_archive.error_archiving'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => $record->isArchived() && ProgramApplicationResource::canRestore($record)),

                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Application / حذف التطبيق')
                    ->modalDescription('Are you sure you want to delete this application? This action cannot be undone. / هل أنت متأكد من حذف هذا التطبيق؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('Delete / حذف')
                    ->action(function ($record) {
                        $approvalService = new ApplicationApprovalService();
                        $result = $approvalService->processAction(
                            'delete',
                            ['program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                            $record->id,
                            'Delete application request / طلب حذف التطبيق'
                        );

                        if ($result['success']) {
                            if ($result['requires_approval']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                    ->body('Your delete request has been submitted for approval. You will be notified once approved.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Application Deleted / تم حذف التطبيق')
                                    ->body('The application has been deleted successfully.')
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
                    ->visible(fn ($record) => auth()->user()?->can('delete ProgramApplication')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('application_archive.archive_selected'))
                        ->color('warning')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->modalHeading(__('application_archive.archive_bulk_heading'))
                        ->modalDescription(__('application_archive.archive_bulk_description'))
                        ->modalSubmitActionLabel(__('application_archive.archive_bulk_confirm'))
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $alreadyArchived = 0;
                            $pendingApproval = 0;

                            foreach ($records as $record) {
                                if (!$record->isArchived()) {
                                    $result = $approvalService->processAction(
                                        'archive',
                                        ['is_archived' => true, 'archived_at' => now(), 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk archive application request / طلب أرشفة جماعية للتطبيقات'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $count++;
                                        }
                                    }
                                } else {
                                    $alreadyArchived++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.applications_archived'))
                                    ->body(__('application_archive.successfully_archived_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} archive requests have been submitted for approval.")
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.warning'))
                                    ->body(__('application_archive.already_archived_count', ['count' => $alreadyArchived]))
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyArchived > 0 && $pendingApproval === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.no_action_taken'))
                                    ->body(__('application_archive.all_selected_already_archived'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive ProgramApplication'))
                        ->authorize(fn () => auth()->user()?->can('archive ProgramApplication')),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('application_archive.restore_selected'))
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->modalHeading(__('application_archive.restore_bulk_heading'))
                        ->modalDescription(__('application_archive.restore_bulk_description'))
                        ->modalSubmitActionLabel(__('application_archive.restore_bulk_confirm'))
                        ->action(function ($records) {
                            $count = 0;
                            $alreadyActive = 0;
                            $errors = [];

                            foreach ($records as $record) {
                                if ($record->isArchived()) {
                                    try {
                                        $record->restore();
                                        $count++;
                                    } catch (\Exception $e) {
                                        $errors[] = "Application ID {$record->id}: " . $e->getMessage();
                                    }
                                } else {
                                    $alreadyActive++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.applications_restored'))
                                    ->body(__('application_archive.successfully_restored_count', ['count' => $count]))
                                    ->success()
                                    ->send();
                            }

                            if ($alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.warning'))
                                    ->body(__('application_archive.already_active_count', ['count' => $alreadyActive]))
                                    ->warning()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.error_archiving'))
                                    ->body(implode('<br>', $errors))
                                    ->danger()
                                    ->send();
                            }

                            if ($count === 0 && $alreadyActive > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('application_archive.no_action_taken'))
                                    ->body(__('application_archive.all_selected_already_active'))
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore ProgramApplication'))
                        ->authorize(fn () => auth()->user()?->can('restore ProgramApplication')),

                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Applications / حذف التطبيقات المحددة')
                        ->modalDescription('Are you sure you want to delete the selected applications? This action cannot be undone. / هل أنت متأكد من حذف التطبيقات المحددة؟ لا يمكن التراجع عن هذا الإجراء.')
                        ->modalSubmitActionLabel('Delete / حذف')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $errors = [];

                            foreach ($records as $record) {
                                $result = $approvalService->processAction(
                                    'delete',
                                    ['program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                    $record->id,
                                    'Bulk delete application request / طلب حذف جماعي للتطبيقات'
                                );

                                if ($result['success']) {
                                    if ($result['requires_approval']) {
                                        $pendingApproval++;
                                    } else {
                                        $count++;
                                    }
                                } else {
                                    $errors[] = $result['message'] ?? 'Unknown error';
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Applications Deleted / تم حذف التطبيقات')
                                    ->body("{$count} application(s) have been deleted successfully. / تم حذف {$count} تطبيق بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} delete request(s) have been submitted for approval. You will be notified once approved. / تم تقديم {$pendingApproval} طلب حذف للموافقة. سيتم إعلامك بمجرد الموافقة.")
                                    ->success()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Some Errors Occurred / حدثت بعض الأخطاء')
                                    ->body(implode('<br>', array_unique($errors)))
                                    ->danger()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken / لم يتم اتخاذ إجراء')
                                    ->body('No applications were deleted. / لم يتم حذف أي تطبيقات.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('delete ProgramApplication'))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk-approve')
                        ->label('Approve Selected')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Applications / موافقة على التطبيقات المحددة')
                        ->modalDescription('Are you sure you want to approve the selected applications? This action will be submitted for approval. / هل أنت متأكد من الموافقة على التطبيقات المحددة؟ سيتم تقديم هذا الإجراء للموافقة.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $skippedArchived = 0;
                            $skippedInvalidStatus = 0;

                            foreach ($records as $record) {
                                // Skip archived records - they cannot be approved/rejected
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                if ($record->isPending() || $record->isRejected()) {
                                    $result = $approvalService->processAction(
                                        'update',
                                        ['status' => 'approved', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk approve application request / طلب موافقة جماعية على التطبيقات'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->approve();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $skippedInvalidStatus++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Applications Approved / تم الموافقة على التطبيقات')
                                    ->body("{$count} application(s) have been approved successfully. / تم الموافقة على {$count} تطبيق بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} approval request(s) have been submitted for approval. / تم تقديم {$pendingApproval} طلب موافقة للموافقة.")
                                    ->success()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Applications Skipped / تم تخطي التطبيقات المؤرشفة')
                                    ->body("{$skippedArchived} archived application(s) were skipped. Archived applications cannot be approved. / تم تخطي {$skippedArchived} تطبيق مؤرشف. لا يمكن الموافقة على التطبيقات المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedInvalidStatus > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Applications Skipped / تم تخطي التطبيقات')
                                    ->body("{$skippedInvalidStatus} application(s) were skipped because they are not in a pending or rejected status. / تم تخطي {$skippedInvalidStatus} تطبيق لأنها ليست في حالة معلقة أو مرفوضة.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $skippedArchived === 0 && $skippedInvalidStatus === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken / لم يتم اتخاذ إجراء')
                                    ->body('No applications were approved. / لم يتم الموافقة على أي تطبيقات.')
                                    ->warning()
                                    ->send();
                            }
                        })->visible(fn () => auth()->user()?->can('update ProgramApplication')),

                    Tables\Actions\BulkAction::make('bulk-reject')
                        ->label('Reject Selected')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Selected Applications / رفض التطبيقات المحددة')
                        ->modalDescription('Are you sure you want to reject the selected applications? This action will be submitted for approval. / هل أنت متأكد من رفض التطبيقات المحددة؟ سيتم تقديم هذا الإجراء للموافقة.')
                        ->action(function ($records) {
                            $approvalService = new ApplicationApprovalService();
                            $count = 0;
                            $pendingApproval = 0;
                            $skippedArchived = 0;
                            $skippedInvalidStatus = 0;

                            foreach ($records as $record) {
                                // Skip archived records - they cannot be approved/rejected
                                if ($record->isArchived()) {
                                    $skippedArchived++;
                                    continue;
                                }

                                if ($record->isPending() || $record->isApproved()) {
                                    $result = $approvalService->processAction(
                                        'update',
                                        ['status' => 'rejected', 'program_id' => $record->program_id, 'title' => $record->program?->title ?? 'N/A'],
                                        $record->id,
                                        'Bulk reject application request / طلب رفض جماعي للتطبيقات'
                                    );

                                    if ($result['success']) {
                                        if ($result['requires_approval']) {
                                            $pendingApproval++;
                                        } else {
                                            $record->reject();
                                            $count++;
                                        }
                                    }
                                } else {
                                    $skippedInvalidStatus++;
                                }
                            }

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Applications Rejected / تم رفض التطبيقات')
                                    ->body("{$count} application(s) have been rejected successfully. / تم رفض {$count} تطبيق بنجاح.")
                                    ->success()
                                    ->send();
                            }

                            if ($pendingApproval > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Requests Submitted for Approval / تم تقديم الطلبات للموافقة')
                                    ->body("{$pendingApproval} rejection request(s) have been submitted for approval. / تم تقديم {$pendingApproval} طلب رفض للموافقة.")
                                    ->success()
                                    ->send();
                            }

                            if ($skippedArchived > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Archived Applications Skipped / تم تخطي التطبيقات المؤرشفة')
                                    ->body("{$skippedArchived} archived application(s) were skipped. Archived applications cannot be rejected. / تم تخطي {$skippedArchived} تطبيق مؤرشف. لا يمكن رفض التطبيقات المؤرشفة.")
                                    ->warning()
                                    ->send();
                            }

                            if ($skippedInvalidStatus > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Applications Skipped / تم تخطي التطبيقات')
                                    ->body("{$skippedInvalidStatus} application(s) were skipped because they are not in a pending or approved status. / تم تخطي {$skippedInvalidStatus} تطبيق لأنها ليست في حالة معلقة أو موافق عليها.")
                                    ->warning()
                                    ->send();
                            }

                            if ($count === 0 && $pendingApproval === 0 && $skippedArchived === 0 && $skippedInvalidStatus === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Action Taken / لم يتم اتخاذ إجراء')
                                    ->body('No applications were rejected. / لم يتم رفض أي تطبيقات.')
                                    ->warning()
                                    ->send();
                            }
                        })->visible(fn () => auth()->user()?->can('update ProgramApplication')),
                ]),

                ExportBulkAction::make()->exporter(ProgramApplicationExporter::class)
                    ->columnMapping(false)
                    ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['team.members.participant', 'comments']))
                    ->fileName(fn (\Filament\Actions\Exports\Models\Export $export) => 'Registration_' . now()->format('Y-m-d_His'))
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
                                'resource' => 'Registration',
                                'file_name' => $export->file_name,
                                'total_rows' => $export->total_rows,
                                'export_timestamp' => now()->toIso8601String(),
                                'criteria' => [
                                    'program_id' => session('current_program_id'),
                                ],
                            ])
                            ->log($user->name . ' exported registration (applications)');
                    })
            ])

            ->filters([

                SelectFilter::make('track_id')
                    ->label('Track')
                    ->placeholder('All Tracks')
                    ->options(fn () => \App\Models\Track::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->where('form_submissions->track', (int) $data['value']);
                        }
                    }),


                SelectFilter::make('sub_track_id')
                    ->label('Sub Track')
                    ->placeholder('All Sub Tracks')
                    ->options(fn () => \App\Models\SubTrack::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->where('form_submissions->sub_track', (int) $data['value']);
                        }
                    }),

                Tables\Filters\SelectFilter::make('has_team')
                    ->label('Has Team')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),

                Tables\Filters\SelectFilter::make('registered_as_team')
                    ->label('Registered as Team')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),

                Tables\Filters\SelectFilter::make('has_idea')
                    ->label('Has Idea')
                    ->options([
                        1 => 'Yes',
                        0 => 'No',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->defaultSort('total_score', 'desc');
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        // Build base query
        $baseQuery = ProgramApplication::query()->submission();

        // Filter by admin's assigned programs (unless super admin)
        if ($user && !$user->isSuperAdmin()) {
            // Get program IDs the user has access to
            $programIds = \App\Models\UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();

            if (!empty($programIds)) {
                $baseQuery->whereIn('program_id', $programIds);
            } else {
                // If user has no assigned programs, show nothing
                $baseQuery->whereRaw('1 = 0');
            }
        } else {
            // Super admin sees all applications
            $baseQuery = ProgramApplication::byProgram()->submission();
        }

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn($query) => clone $baseQuery),

            'active' => Tab::make(__('application_archive.active_applications'))
                ->badge((clone $baseQuery)->active()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()),

            'pending' => Tab::make('Pending')
                ->badge((clone $baseQuery)->active()->pending()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()->pending()),
            'approved' => Tab::make('Approved')
                ->badge((clone $baseQuery)->active()->approved()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()->approved()),
            'rejected' => Tab::make('Rejected')
                ->badge((clone $baseQuery)->active()->rejected()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->active()->rejected()),
        ];

        // Only add archived tab for users with archive or restore permissions
        if (auth()->user()?->can('archive ProgramApplication') || auth()->user()?->can('restore ProgramApplication')) {
            $tabs['archived'] = Tab::make(__('application_archive.archived_applications'))
                ->badge((clone $baseQuery)->archived()->count())
                ->modifyQueryUsing(fn($query) => (clone $baseQuery)->archived());
        }

        return $tabs;
    }
}
