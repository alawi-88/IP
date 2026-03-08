<?php

namespace App\Filament\Resources\ProgramApplicationResource\Pages;

use App\Filament\Resources\ProgramApplicationResource;
use App\Models\ProgramApplication;
use App\Models\RegistrationFormConfig;
use App\Services\AiEvaluationService;
use App\Models\FormField;
use App\Services\RegistrationEvaluationService;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Components\ViewField;
class ViewProgramApplication extends ViewRecord
{
    protected static string $resource = ProgramApplicationResource::class;

    /**
     * Resolve the record and check authorization to prevent IDOR
     * Returns 404 instead of 403 to avoid revealing resource existence
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to view this application
        // Return 404 to avoid revealing that the resource exists but user lacks access
        if (!ProgramApplicationResource::canView($record)) {
            abort(404);
        }

        return $record;
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
                ->visible(fn () => !$this->record->isArchived() && $this->record->isPending())
                ->requiresConfirmation(true)
                ->modalHeading('Approve Application / موافقة على التطبيق')
                ->modalDescription(function () use ($hasScoring, $hasCriteria, $hasExistingScores) {
                    if ($hasScoring && $hasCriteria) {
                        if ($hasExistingScores) {
                            return 'This application already has scores assigned. You can review and modify them before approving. / هذا التطبيق لديه نقاط مخصصة بالفعل. يمكنك مراجعتها وتعديلها قبل الموافقة.';
                        }
                        return 'You must enter scores for each assessment criterion before approving. / يجب إدخال النقاط لكل معيار تقييم قبل الموافقة.';
                    }
                    return 'Are you sure you want to approve this application? / هل أنت متأكد من الموافقة على هذا التطبيق؟';
                })
                ->form(function () use ($hasScoring, $criteria, $hasCriteria, $hasExistingScores) {
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
                    // Scores are ALWAYS required when scoring is enabled
                    foreach ($criteria as $criterion) {
                        $criterionId = (string) $criterion->id;
                        $existingScore = $hasExistingScores ? ($this->record->assessment_scores[$criterion->id] ?? 0) : null;

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
                            ])
                            ->validationMessages([
                                'required' => __('scoring.score_required'),
                                'integer' => __('scoring.score_must_be_integer'),
                                'min' => __('scoring.score_min_validation'),
                                'max' => __('scoring.score_max_validation', ['max' => $criterion->max_score]),
                            ])
                            ->extraAttributes([
                                'data-criterion-id' => $criterion->id,
                                'data-max-score' => $criterion->max_score,
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
                ->action(function (array $data) use ($hasScoring, $hasCriteria, $hasExistingScores) {
                    // REQUIRED: If scoring is enabled, scores MUST be provided
                    if ($hasScoring && $hasCriteria) {
                        $criteria = $this->record->getAssessmentCriteria();

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
                        $this->record->setAssessmentScores($scores);
                    }

                    // Record reviewer info
                    $this->record->update([
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    // Approve the application (only after successful scoring if required)
                    $this->record->approve();

                    // Send decision notification
                    try {
                        $this->record->participant?->notify(
                            new \App\Notifications\Participant\RegistrationDecisionNotification($this->record, 'approved')
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Decision notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Application Approved / تم الموافقة على التطبيق')
                        ->body($this->record->hasScoringEnabled() && $this->record->total_score !== null
                            ? "The application has been approved with a total score of {$this->record->total_score}. / تم الموافقة على التطبيق بإجمالي نقاط {$this->record->total_score}."
                            : 'The application has been approved successfully. / تم الموافقة على التطبيق بنجاح.')
                        ->success()
                        ->send();
                }),

            // IN-2060: Reject with mandatory reason
            Action::make('reject')
                ->label('Reject / رفض')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => !$this->record->isArchived() && $this->record->isPending())
                ->modalHeading('Reject Application / رفض الطلب')
                ->form([
                    Forms\Components\Textarea::make('decision_reason')
                        ->label('Rejection Reason / سبب الرفض')
                        ->required()
                        ->rows(4)
                        ->helperText('This reason will be visible to the participant. / سيكون هذا السبب مرئياً للمشارك.'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'decision_reason' => $data['decision_reason'],
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);
                    $this->record->reject();

                    // Send notification with reason
                    try {
                        $this->record->participant?->notify(
                            new \App\Notifications\Participant\RegistrationDecisionNotification($this->record, 'rejected', $data['decision_reason'])
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Decision notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Application Rejected / تم رفض التطبيق')
                        ->body('The application has been rejected with reason provided.')
                        ->danger()
                        ->send();
                }),

            // IN-2062: Request Field Edits
            Action::make('requestEdit')
                ->label('Request Edit / طلب تعديل')
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => !$this->record->isArchived() && $this->record->isPending())
                ->modalHeading('Request Application Edit / طلب تعديل الطلب')
                ->modalDescription('Select which fields the participant can edit and provide instructions. / حدد الحقول التي يمكن للمشارك تعديلها وقدم التعليمات.')
                ->form(function () {
                    // Get form fields for this application's form
                    $formFields = FormField::where('form_id', $this->record->form_id)
                        ->orderBy('sort_order')
                        ->get();

                    $fieldOptions = $formFields->mapWithKeys(function ($field) {
                        $label = $field->getTranslation('label', 'en') ?? $field->slug;
                        return [$field->slug => $label];
                    })->toArray();

                    return [
                        Forms\Components\CheckboxList::make('editable_fields')
                            ->label('Fields to Edit / الحقول للتعديل')
                            ->options($fieldOptions)
                            ->required()
                            ->columns(2)
                            ->helperText('Select the fields that the participant will be allowed to edit.'),

                        Forms\Components\Textarea::make('edit_notes_en')
                            ->label('Edit Instructions (English)')
                            ->required()
                            ->rows(3)
                            ->helperText('Explain what changes are needed.'),

                        Forms\Components\Textarea::make('edit_notes_ar')
                            ->label('تعليمات التعديل (عربي)')
                            ->rows(3)
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                    ];
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'edit_requested',
                        'editable_fields' => $data['editable_fields'],
                        'edit_notes' => [
                            'en' => $data['edit_notes_en'],
                            'ar' => $data['edit_notes_ar'] ?? '',
                        ],
                        'edit_requested_at' => now(),
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    // Send edit request notification
                    try {
                        $this->record->participant?->notify(
                            new \App\Notifications\Participant\EditRequestNotification($this->record)
                        );
                    } catch (\Exception $e) {
                        \Log::warning('Edit request notification failed: ' . $e->getMessage());
                    }

                    Notification::make()
                        ->title('Edit Request Sent / تم إرسال طلب التعديل')
                        ->body('The participant has been notified to edit their application.')
                        ->warning()
                        ->send();
                }),

            // IN-2057: View Evaluation Score Breakdown
            Action::make('viewScoreBreakdown')
                ->label('Score Breakdown / تفصيل الدرجات')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->visible(fn () => $this->record->final_evaluation_score !== null
                    || \App\Models\RegistrationEvaluation::where('program_application_id', $this->record->id)->exists())
                ->modalHeading('Evaluation Score Breakdown / تفصيل درجات التقييم')
                ->modalContent(function () {
                    $service = new RegistrationEvaluationService();
                    $breakdown = $service->getScoreBreakdown($this->record->id);

                    return view('filament.modals.score-breakdown', ['breakdown' => $breakdown]);
                })
                ->modalSubmitAction(false),

            Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Application / استعادة التطبيق')
                ->modalDescription('Are you sure you want to restore this application? / هل أنت متأكد من استعادة هذا التطبيق؟')
                ->authorize(fn () => ProgramApplicationResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    Notification::make()
                        ->title('Application Restored / تم استعادة التطبيق')
                        ->body('The application has been restored successfully. / تم استعادة التطبيق بنجاح.')
                        ->success()
                        ->send();

                    $this->redirect(ProgramApplicationResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(ProgramApplication::details());

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
        // Program must have AI config via the form
        $config = \App\Models\FormAiScoringConfig::where('form_id', $this->record->form_id)->first();
        if (!$config) {
            return false;
        }

        // Form must have active assessment criteria
        $criteria = $config->activeAssessmentCriteria()->with('formFields')->get();
        if ($criteria->isEmpty()) {
            return false;
        }

        // Each criterion should have at least one mapped field (Field Mapping Matrix)
        foreach ($criteria as $criterion) {
            if ($criterion->formFields->isEmpty()) {
                return false;
            }
        }

        return true;
    }
}
