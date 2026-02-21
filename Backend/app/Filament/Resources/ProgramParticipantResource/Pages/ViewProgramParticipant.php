<?php

namespace App\Filament\Resources\ProgramParticipantResource\Pages;

use App\Filament\Resources\ProgramParticipantResource;
use App\Models\ProgramApplication;
use App\Models\Project;
use App\Models\Stage;
use App\Services\ApplicationApprovalService;
use App\Services\AiEvaluationService;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ViewProgramParticipant extends ViewRecord
{
    protected static string $resource = ProgramParticipantResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = ProgramApplication::with([
            'program.stages' => function ($q) {
                $q->where('slug', '!=', 'registration')
                  ->orderBy('starts_at', 'asc');
            },
            'projects.form',
            'projects.comments',
            'team.members.participant',
            'participant'
        ])->findOrFail($key);

        // Check authorization
        if (!ProgramParticipantResource::canView($record)) {
            abort(404);
        }

        return $record;
    }

    public function getTitle(): string | Htmlable
    {
        $name = $this->record->participant?->name
            ?? $this->record->team_name
            ?? ($this->record->form_submissions['participant_name'] ?? null)
            ?? "Participant #{$this->record->id}";

        return "Participant Details: {$name}";
    }

    public function getHeading(): string | Htmlable
    {
        return $this->getTitle();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Home',
            ProgramParticipantResource::getUrl('index') => 'Participants',
            ProgramParticipantResource::getUrl('view', ['record' => $this->record]) => 'Participant Details',
        ];
    }

    protected function getHeaderActions(): array
    {
        $hasScoring = $this->record->hasScoringEnabled();
        $criteria = $this->record->getAssessmentCriteria();
        $hasCriteria = $criteria->isNotEmpty();
        $hasExistingScores = $this->record->assessment_scores !== null && !empty($this->record->assessment_scores);

        return [
            Action::make('aiEvaluation')
                ->label('AI Evaluation / تقييم بالذكاء الاصطناعي')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->isArchived()
                    && $this->record->isPending()
                    && !$this->record->hasCompletedAiEvaluation()
                    && $this->hasAiEvaluationPrerequisites())
                ->action(function () {
                    $service = new AiEvaluationService();
                    $answers = $this->getFormSubmissionsArray();
                    $result = $service->evaluate(
                        $this->record->form_id,
                        $answers,
                        $this->record->id,
                        'program_application'
                    );

                    if (!$result['success']) {
                        Notification::make()
                            ->title('AI Evaluation Failed')
                            ->body($result['message'] ?? 'Unable to complete AI evaluation.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->record->applyAiEvaluationResponse(
                        $result['response'] ?? [],
                        'completed',
                        $result['response']['message'] ?? null
                    );

                    Notification::make()
                        ->title('AI Evaluation Completed')
                        ->body('AI response saved successfully.')
                        ->success()
                        ->send();
                }),

            Action::make('approve')
                ->label('Approve / موافقة')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => !$this->record->isArchived() && ($this->record->isPending() || $this->record->isRejected()))
                ->requiresConfirmation(true)
                ->modalHeading('Approve Participant / موافقة على المشارك')
                ->modalDescription(function () use ($hasScoring, $hasCriteria, $hasExistingScores) {
                    if ($hasScoring && $hasCriteria) {
                        if ($hasExistingScores) {
                            return 'This participant already has scores assigned. You can review and modify them before approving. / هذا المشارك لديه نقاط مخصصة بالفعل. يمكنك مراجعتها وتعديلها قبل الموافقة.';
                        }
                        return 'You must enter scores for each assessment criterion before approving. / يجب إدخال النقاط لكل معيار تقييم قبل الموافقة.';
                    }
                    return 'Are you sure you want to approve this participant? This action will be submitted for approval. / هل أنت متأكد من الموافقة على هذا المشارك؟ سيتم تقديم هذا الإجراء للموافقة.';
                })
                ->form(function () use ($hasScoring, $criteria, $hasCriteria, $hasExistingScores) {
                    if (!$hasScoring || !$hasCriteria) {
                        return [];
                    }

                    $formFields = [];
                    $criteriaSummary = $criteria->map(function ($criterion) {
                        return "• {$criterion->description} (Max: {$criterion->max_score})";
                    })->join("\n");

                    $formFields[] = Forms\Components\Section::make('Required Scoring / التقييم المطلوب')
                        ->description('You must enter scores for all assessment criteria before approving this participant. / يجب إدخال النقاط لجميع معايير التقييم قبل الموافقة على هذا المشارك.')
                        ->schema([
                            Forms\Components\Placeholder::make('criteria_summary')
                                ->label('Assessment Criteria / معايير التقييم')
                                ->content($criteriaSummary)
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull();

                    $maxTotalScore = 0;
                    foreach ($criteria as $criterion) {
                        $maxTotalScore += $criterion->max_score;
                    }

                    foreach ($criteria as $criterion) {
                        $criterionId = (string) $criterion->id;
                        $existingScore = $hasExistingScores ? ($this->record->assessment_scores[$criterion->id] ?? 0) : null;
                        $defaultScore = $existingScore ?? 0;
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
                                $total = 0;
                                foreach ($criteria as $criterion) {
                                    $score = $get("scores.{$criterion->id}") ?? 0;
                                    $total += (int) $score;
                                }
                                $totalDisplay = $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                                $set('total_score_display', $totalDisplay);
                            });
                    }

                    $formFields[] = Forms\Components\TextInput::make('total_score_display')
                        ->label('Total Score / النقاط الإجمالية')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(function (callable $get) use ($criteria, $maxTotalScore) {
                            $total = 0;
                            foreach ($criteria as $criterion) {
                                $score = $get("scores.{$criterion->id}") ?? 0;
                                $total += (int) $score;
                            }
                            return $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                        })
                        ->reactive()
                        ->formatStateUsing(function ($state, callable $get) use ($criteria, $maxTotalScore) {
                            $total = 0;
                            foreach ($criteria as $criterion) {
                                $score = $get("scores.{$criterion->id}") ?? 0;
                                $total += (int) $score;
                            }
                            return $maxTotalScore > 0 ? "{$total} / {$maxTotalScore}" : (string) $total;
                        })
                        ->extraAttributes(['class' => 'text-lg font-bold'])
                        ->columnSpanFull();

                    return $formFields;
                })
                ->action(function (array $data) use ($hasScoring, $hasCriteria) {
                    if ($hasScoring && $hasCriteria) {
                        $criteria = $this->record->getAssessmentCriteria();

                        if (!isset($data['scores']) || empty($data['scores'])) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'scores' => __('scoring.scores_required_for_approval'),
                            ]);
                        }

                        $missingScores = [];
                        $invalidScores = [];

                        foreach ($criteria as $criterion) {
                            $criterionId = (string) $criterion->id;

                            if (!isset($data['scores'][$criterionId]) || $data['scores'][$criterionId] === null || $data['scores'][$criterionId] === '') {
                                $missingScores[] = $criterion->description;
                                continue;
                            }

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

                        $scores = [];
                        foreach ($data['scores'] as $criterionId => $score) {
                            $scores[(int) $criterionId] = (int) $score;
                        }

                        $this->record->setAssessmentScores($scores);
                    }

                    $approvalService = new ApplicationApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        ['status' => 'approved', 'program_id' => $this->record->program_id, 'title' => $this->record->program?->title ?? 'N/A'],
                        $this->record->id,
                        'Approve participant request'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your approval request has been submitted for approval. You will be notified once approved.')
                                ->success()
                                ->send();
                        } else {
                            $this->record->approve();
                            Notification::make()
                                ->title('Participant Approved / تم الموافقة على المشارك')
                                ->body($this->record->hasScoringEnabled() && $this->record->total_score !== null
                                    ? "The participant has been approved with a total score of {$this->record->total_score}. / تم الموافقة على المشارك بإجمالي نقاط {$this->record->total_score}."
                                    : 'The participant has been approved successfully. / تم الموافقة على المشارك بنجاح.')
                                ->success()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject / رفض')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Reject Participant / رفض المشارك')
                ->modalDescription('Are you sure you want to reject this participant? This action will be submitted for approval. / هل أنت متأكد من رفض هذا المشارك؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->visible(fn () => !$this->record->isArchived() && ($this->record->isPending() || $this->record->isApproved()))
                ->action(function () {
                    $approvalService = new ApplicationApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        ['status' => 'rejected', 'program_id' => $this->record->program_id, 'title' => $this->record->program?->title ?? 'N/A'],
                        $this->record->id,
                        'Reject participant request'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your rejection request has been submitted for approval. You will be notified once approved.')
                                ->success()
                                ->send();
                        } else {
                            $this->record->reject();
                            Notification::make()
                                ->title('Participant Rejected / تم رفض المشارك')
                                ->body('The participant has been rejected successfully.')
                                ->success()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('disqualify')
                ->label('Disqualify / استبعاد')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->requiresConfirmation()
                ->modalHeading('Disqualify Participant / استبعاد المشارك')
                ->modalDescription('Are you sure you want to disqualify this participant? This will mark them as disqualified and they will not be able to continue in the program. This action will be submitted for approval. / هل أنت متأكد من استبعاد هذا المشارك؟ سيتم تمييزه كمستبعد ولن يتمكن من متابعة المسابقة. سيتم تقديم هذا الإجراء للموافقة.')
                ->visible(fn () => !$this->record->isArchived() && $this->record->isApproved())
                ->action(function () {
                    $approvalService = new ApplicationApprovalService();
                    $result = $approvalService->processAction(
                        'update',
                        ['status' => 'rejected', 'program_id' => $this->record->program_id, 'title' => $this->record->program?->title ?? 'N/A'],
                        $this->record->id,
                        'Disqualify participant request'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your disqualification request has been submitted for approval. You will be notified once approved.')
                                ->success()
                                ->send();
                        } else {
                            $this->record->reject();
                            Notification::make()
                                ->title('Participant Disqualified / تم استبعاد المشارك')
                                ->body('The participant has been disqualified successfully.')
                                ->success()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('archive')
                ->label('Deactivate / إلغاء التفعيل')
                ->color('warning')
                ->icon('heroicon-o-archive-box')
                ->requiresConfirmation()
                ->modalHeading('Deactivate Participant / إلغاء تفعيل المشارك')
                ->modalDescription('Are you sure you want to deactivate this participant? This action will be submitted for approval. / هل أنت متأكد من إلغاء تفعيل هذا المشارك؟ سيتم تقديم هذا الإجراء للموافقة.')
                ->visible(fn () => !$this->record->isArchived())
                ->action(function () {
                    $approvalService = new ApplicationApprovalService();
                    $result = $approvalService->processAction(
                        'archive',
                        ['is_archived' => true, 'archived_at' => now(), 'program_id' => $this->record->program_id, 'title' => $this->record->program?->title ?? 'N/A'],
                        $this->record->id,
                        'Archive participant request'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your deactivate request has been submitted for approval. You will be notified once approved.')
                                ->success()
                                ->send();
                        } else {
                            $this->record->archive();
                            Notification::make()
                                ->title('Participant Deactivated / تم إلغاء تفعيل المشارك')
                                ->body('The participant has been deactivated successfully.')
                                ->success()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('restore')
                ->label('Activate / تفعيل')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Activate Participant / تفعيل المشارك')
                ->modalDescription('Are you sure you want to activate this participant? / هل أنت متأكد من تفعيل هذا المشارك؟')
                ->authorize(fn () => ProgramParticipantResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    try {
                        $this->record->restore();
                        Notification::make()
                            ->title('Participant Activated / تم تفعيل المشارك')
                            ->body('The participant has been activated successfully. / تم تفعيل المشارك بنجاح.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('delete')
                ->label('Delete / حذف')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Participant / حذف المشارك')
                ->modalDescription('Are you sure you want to delete this participant? This action cannot be undone. This action will be submitted for approval. / هل أنت متأكد من حذف هذا المشارك؟ لا يمكن التراجع عن هذا الإجراء. سيتم تقديم هذا الإجراء للموافقة.')
                ->visible(fn () => auth()->user()?->can('delete ProgramApplication'))
                ->action(function () {
                    $approvalService = new ApplicationApprovalService();
                    $result = $approvalService->processAction(
                        'delete',
                        ['program_id' => $this->record->program_id, 'title' => $this->record->program?->title ?? 'N/A'],
                        $this->record->id,
                        'Delete participant request'
                    );

                    if ($result['success']) {
                        if ($result['requires_approval']) {
                            Notification::make()
                                ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                ->body('Your delete request has been submitted for approval. You will be notified once approved.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Participant Deleted / تم حذف المشارك')
                                ->body('The participant has been deleted successfully.')
                                ->success()
                                ->send();
                            $this->redirect(ProgramParticipantResource::getUrl('index'));
                        }
                    } else {
                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema($this->buildInfolistSchema());
    }

    protected function buildInfolistSchema(): array
    {
        $schema = [];

        // Basic Information
        $schema[] = Section::make('Basic Information / المعلومات الأساسية')
            ->columns(4)
            ->schema([
                TextEntry::make('id')
                    ->label('Application ID / رقم الطلب')
                    ->getStateUsing(fn($record) => str($record->id)->prepend('#')),

                TextEntry::make('status')
                    ->label('Registration Status / حالة التسجيل')
                    ->badge()
                    ->color(fn($record) => match ($record->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->getStateUsing(fn($record) => str($record->status)->ucfirst()),

                TextEntry::make('program.title')
                    ->label('Program / البرنامج'),

                TextEntry::make('created_at')
                    ->label('Registration Date / تاريخ التسجيل')
                    ->dateTime(),
            ]);

        // Registration Score (if scoring is enabled and total_score exists)
        if ($this->record->hasScoringEnabled() && $this->record->total_score !== null) {
            // Detailed Assessment Scores (if detailed scores exist)
            if ($this->record->assessment_scores !== null && !empty($this->record->assessment_scores)) {
                $schema[] = Section::make('Assessment Scores / نقاط التقييم')
                    ->schema(function ($record) {
                        $entries = [];
                        $criteria = $record->getAssessmentCriteria();
                        $scores = $record->assessment_scores ?? [];

                        $maxTotalScore = 0;
                        foreach ($criteria as $criterion) {
                            $score = $scores[$criterion->id] ?? 0;

                            // Calculate maximum total score
                            $maxTotalScore += $criterion->max_score;

                            $label = $criterion->description;
                            $content = "{$score} / {$criterion->max_score}";

                            $entries[] = TextEntry::make("criterion_{$criterion->id}")
                                ->label($label)
                                ->default($content)
                                ->badge()
                                ->color('info');
                        }

                        // Display total score with maximum (e.g., "35/50")
                        $entries[] = TextEntry::make('total_score')
                            ->label('Total Score / النقاط الإجمالية')
                            ->getStateUsing(function () use ($record, $maxTotalScore) {
                                $totalScore = $record->total_score ?? 0;
                                return $maxTotalScore > 0
                                    ? "{$totalScore} / {$maxTotalScore}"
                                    : (string) $totalScore;
                            })
                            ->badge()
                            ->color('success')
                            ->size('lg');

                        return $entries;
                    })
                    ->columns(2);
            } else {
                // Simple total score display when detailed scores don't exist
                $schema[] = Section::make('Registration Score / نقاط التسجيل')
                    ->schema([
                        TextEntry::make('total_score')
                            ->label('Total Score / النقاط الإجمالية')
                            ->getStateUsing(fn($record) => (string) ($record->total_score ?? 0))
                            ->badge()
                            ->color('success')
                            ->size('lg'),
                    ]);
            }
        }

        // Registration Application Form
        $schema[] = Section::make('Registration Application / طلب التسجيل')
            ->schema(function ($record) {
                $entries = [];
                $formSubmissions = $this->getFormSubmissionsArray($record);

                if (empty($formSubmissions)) {
                    $entries[] = TextEntry::make('no_data')
                        ->label('No Registration Data Available / لا توجد بيانات تسجيل متاحة')
                        ->default('—')
                        ->columnSpanFull();
                    return $entries;
                }

                // Get form fields to display all fields
                $formFields = collect();
                if ($record->form_id && $record->form) {
                    $formFields = $record->form->fields()
                        ->whereNotIn('type', ['section_header', 'paragraph'])
                        ->orderBy('sort')
                        ->get();
                }

                // Display form fields
                foreach ($formFields as $field) {
                    $key = $field->slug;
                    $value = $formSubmissions[$key] ?? null;

                    if ($value === null) {
                        continue; // Skip missing fields
                    }

                    $label = is_array($field->label)
                        ? ($field->label['en'] ?? $field->label['ar'] ?? Str::headline($key))
                        : ($field->label ?? Str::headline($key));

                    // Handle file uploads
                    if ($field->type === 'file' && is_string($value)) {
                        $entries[] = ViewEntry::make("form_submissions_{$key}")
                            ->label($label)
                            ->view('filament.custom-entries.file-preview')
                            ->viewData([
                                'url' => Storage::url($value),
                                'filename' => basename($value),
                                'isImage' => preg_match('/\.(jpg|jpeg|png|webp)$/i', $value),
                                'label' => $label,
                            ]);
                        continue;
                    }

                    // Handle arrays (multi-select, checkbox)
                    $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                    if (is_array($value) || $isCommaSeparatedString) {
                        // Convert string to array if needed
                        $isArrayValue = is_array($value);
                        $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                        // Pass the field object directly to avoid re-querying
                        $formattedValue = \App\Models\ProgramApplication::formatFormFieldValueStatic($key, $arrayValue, $field);
                        $entries[] = TextEntry::make("form_submissions_{$key}")
                            ->label($label)
                            ->default($formattedValue ?? '—');
                        continue;

                    }
                    // if (is_array($value)) {                       
                    //     $formattedValue = $this->formatFormFieldValue($key, $value, $field);
                    //     $entries[] = TextEntry::make("form_submissions_{$key}")
                    //         ->label($label)
                    //         ->default($formattedValue ?? '—');
                    //     continue;
                    // }

                    // Handle regular values
                    $formattedValue = $this->formatFormFieldValue($key, $value, $field);
                    $entries[] = TextEntry::make("form_submissions_{$key}")
                        ->label($label)
                        ->default($formattedValue ?? '—');
                }

                return $entries;
            })
            ->columns(2);

        // Team Members (if team)
        if ($this->record->has_team) {
            $schema[] = Section::make('Team Information / معلومات الفريق')
                ->schema([
                    TextEntry::make('team.name')
                        ->label('Team Name / اسم الفريق')
                        ->getStateUsing(fn($record) => $record->team?->name ?? $record->team_name ?? '—'),

                    ViewEntry::make('team.logo')
                        ->label('Team Logo / شعار الفريق')
                        ->view('filament.custom-entries.file-preview')
                        ->viewData(fn($record) => [
                            'url' => ($record->team?->logo ?? $record->team_logo) ? Storage::url(ltrim(str_replace(Storage::url('/'), '', $record->team?->logo ?? $record->team_logo), '/')) : null,
                            'filename' => ($record->team?->logo ?? $record->team_logo) ? basename($record->team?->logo ?? $record->team_logo) : '',
                            'isImage' => ($record->team?->logo ?? $record->team_logo) ? preg_match('/\.(jpg|jpeg|png|webp)$/i', $record->team?->logo ?? $record->team_logo) : false,
                            'label' => 'Team Logo',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2);

            $schema[] = Section::make('Team Members / أعضاء الفريق')
                ->schema(function ($record) {
                    $team = $record->team;
                    if (!$team || !$team->members || $team->members->isEmpty()) {
                        return [
                            TextEntry::make('no_members')
                                ->label('No Team Members Found / لم يتم العثور على أعضاء الفريق')
                                ->default('—')
                                ->columnSpanFull(),
                        ];
                    }

                    return [
                        RepeatableEntry::make('team.members')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('participant.name')
                                    ->label('Name / الاسم')
                                    ->default('—'),
                                TextEntry::make('participant.email')
                                    ->label('Email / البريد الإلكتروني')
                                    ->default('—'),
                                TextEntry::make('participant.serial_number')
                                    ->label('Serial Number / الرقم التسلسلي')
                                    ->default('—'),
                                IconEntry::make('is_leader')
                                    ->boolean()
                                    ->label('Team Leader / قائد الفريق'),
                            ]),
                    ];
                });
        }

        // Stages and Projects
        $program = $this->record->program;
        if ($program && $program->stages) {
            $stages = $program->stages->where('slug', '!=', 'registration');

            if ($stages->isNotEmpty()) {
                foreach ($stages as $stage) {
                    $schema[] = Section::make($stage->title . ' / ' . ($stage->getTranslation('title', 'ar') ?? ''))
                        ->schema(function ($record) use ($stage) {
                            $formIds = $stage->getFormIds();

                            if (empty($formIds)) {
                                return [
                                    TextEntry::make('no_forms')
                                        ->label('No Forms Found / لم يتم العثور على نماذج')
                                        ->default('—')
                                        ->columnSpanFull(),
                                ];
                            }

                            // Get projects for this stage
                            $projects = Project::where('application_id', $record->id)
                                ->whereIn('form_id', $formIds)
                                ->where('is_archived', false)
                                ->with(['comments', 'form'])
                                ->get();

                            if ($projects->isEmpty()) {
                                return [
                                    TextEntry::make('no_projects')
                                        ->label('No Project Data Found / لم يتم العثور على بيانات المشروع')
                                        ->default('—')
                                        ->columnSpanFull(),
                                ];
                            }

                            $entries = [];
                            foreach ($projects as $index => $project) {
                                $submissions = $project->form_submissions;
                                if ($submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
                                    $submissions = $submissions->toArray();
                                }

                                $projectName = $submissions['project_name'] ?? "Project #{$project->id}";

                                $entries[] = Section::make($projectName)
                                    ->schema([
                                        // Project Status
                                        TextEntry::make("project_{$project->id}_status")
                                            ->label('Submission Status / حالة الإرسال')
                                            ->badge()
                                            ->color(fn() => match($project->status) {
                                                'submitted' => 'success',
                                                'pending' => 'warning',
                                                'qualified' => 'success',
                                                'not_qualified' => 'danger',
                                                default => 'gray',
                                            })
                                            ->getStateUsing(fn() => str($project->status)->headline()),

                                        // Project Score
                                        TextEntry::make("project_{$project->id}_score")
                                            ->label('Score / النقاط')
                                            ->getStateUsing(function () use ($project) {
                                                if ($project->total_score !== null) {
                                                    return number_format($project->total_score, 2);
                                                }
                                                return '—';
                                            })
                                            ->badge()
                                            ->color('info'),

                                        // Project Description
                                        TextEntry::make("project_{$project->id}_description")
                                            ->label('Description / الوصف')
                                            ->getStateUsing(function () use ($submissions) {
                                                return $submissions['project_description']
                                                    ?? $submissions['description']
                                                    ?? '—';
                                            })
                                            ->columnSpanFull(),

                                        // Project Files
                                        ViewEntry::make("project_{$project->id}_files")
                                            ->label('Project Files / ملفات المشروع')
                                            ->view('filament.custom-entries.project-files')
                                            ->viewData(function () use ($submissions) {
                                                $files = [];
                                                foreach ($submissions as $key => $value) {
                                                    if (is_string($value) && preg_match('/\.(pdf|doc|docx|zip|jpg|jpeg|png)$/i', $value)) {
                                                        $files[] = [
                                                            'url' => Storage::url($value),
                                                            'filename' => basename($value),
                                                            'isImage' => preg_match('/\.(jpg|jpeg|png|webp)$/i', $value),
                                                        ];
                                                    }
                                                }

                                                return ['files' => $files];
                                            })
                                            ->columnSpanFull(),

                                        // Project Feedback/Comments
                                        TextEntry::make("project_{$project->id}_comments")
                                            ->label('Feedback / التعليقات')
                                            ->getStateUsing(function () use ($project) {
                                                $comments = $project->comments ?? collect();
                                                if ($comments->isEmpty()) {
                                                    return 'No feedback available / لا توجد تعليقات متاحة';
                                                }

                                                $feedbackList = $comments->map(function ($comment) {
                                                    $author = 'Unknown';
                                                    if ($comment->user_id && !$comment->author_type) {
                                                        $author = $comment->user?->name ?? 'Admin';
                                                    } elseif ($comment->author) {
                                                        $author = $comment->author->name ?? 'Participant';
                                                    }

                                                    $date = $comment->created_at ? $comment->created_at->format('Y-m-d H:i') : '—';
                                                    $commentText = $comment->comment ?? '—';

                                                    return "• {$author} ({$date}): {$commentText}";
                                                })->join("\n");

                                                return $feedbackList;
                                            })
                                            ->columnSpanFull()
                                            ->markdown(),
                                    ])
                                    ->columns(2);
                            }

                            return $entries;
                        });
                }
            } else {
                $schema[] = Section::make('Stages / المراحل')
                    ->schema([
                        TextEntry::make('no_stages')
                            ->label('No Stages Found / لم يتم العثور على مراحل')
                            ->default('—')
                            ->columnSpanFull(),
                    ]);
            }
        }

        return $schema;
    }

    private function getFormSubmissionsArray($record = null): array
    {
        $record = $record ?? $this->record;
        $submissions = $record->form_submissions;

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

    private function formatFormFieldValue($fieldSlug, $value, $formField = null)
    {
        // Handle arrays
        if (is_array($value)) {
            return implode(', ', array_filter($value));
        }

        // Handle numeric values for option fields
        if (is_numeric($value) && $formField && $formField->options) {
            $options = $formField->options;
            if (is_array($options)) {
                $index = (int)$value - 1;
                if (isset($options[$index])) {
                    $option = $options[$index];
                    if (is_array($option)) {
                        return $option['en'] ?? $option['ar'] ?? $value;
                    }
                    return $option;
                }
            }
        }

        return $value;
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
