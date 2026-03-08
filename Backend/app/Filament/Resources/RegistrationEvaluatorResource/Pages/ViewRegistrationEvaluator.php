<?php

namespace App\Filament\Resources\RegistrationEvaluatorResource\Pages;

use App\Filament\Resources\RegistrationEvaluatorResource;
use App\Models\ProgramApplication;
use App\Models\RegistrationEvaluation;
use App\Models\RegistrationEvaluationCriterion;
use App\Models\RegistrationEvaluationForm;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistrationEvaluator extends ViewRecord
{
    protected static string $resource = RegistrationEvaluatorResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Evaluator Details / تفاصيل المقيّم')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Evaluator Name'),
                    TextEntry::make('user.email')
                        ->label('Email'),
                    TextEntry::make('program.title')
                        ->label('Program')
                        ->getStateUsing(fn ($record) => $record->program?->getTranslation('title', 'en')),
                    IconEntry::make('is_active')
                        ->boolean()
                        ->label('Active'),
                ]),

            Section::make('Assigned Sections / الأقسام المعينة')
                ->schema([
                    TextEntry::make('assigned_forms_list')
                        ->label('Evaluation Forms')
                        ->getStateUsing(function ($record) {
                            $forms = $record->assignedForms;
                            if ($forms->isEmpty()) return 'No sections assigned';
                            return $forms->map(fn ($f) => $f->getTranslation('name', 'en'))->join(', ');
                        }),
                ]),

            Section::make('Evaluation Summary / ملخص التقييم')
                ->schema([
                    TextEntry::make('total_evaluations')
                        ->label('Total Evaluations Submitted')
                        ->getStateUsing(fn ($record) => $record->evaluations()->count()),
                    TextEntry::make('applications_evaluated')
                        ->label('Applications Evaluated')
                        ->getStateUsing(fn ($record) => $record->evaluations()->distinct('program_application_id')->count('program_application_id')),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('evaluateApplication')
                ->label('Score Application / تقييم طلب')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->form(function () {
                    $programId = $this->record->program_id;
                    $assignedFormIds = $this->record->assignedForms()->pluck('registration_evaluation_forms.id')->toArray();

                    return [
                        Forms\Components\Select::make('application_id')
                            ->label('Application / الطلب')
                            ->options(function () use ($programId) {
                                return ProgramApplication::where('program_id', $programId)
                                    ->where('status', 'pending')
                                    ->get()
                                    ->mapWithKeys(fn ($a) => [$a->id => "#{$a->id} - " . ($a->participant?->name ?? 'N/A')]);
                            })
                            ->required()
                            ->searchable(),

                        Forms\Components\Repeater::make('scores')
                            ->label('Criteria Scores / درجات المعايير')
                            ->schema(function () use ($assignedFormIds) {
                                $criteria = RegistrationEvaluationCriterion::whereIn('registration_evaluation_form_id', $assignedFormIds)
                                    ->orderBy('sort_order')
                                    ->get();

                                $fields = [];
                                foreach ($criteria as $criterion) {
                                    $fields[] = Forms\Components\TextInput::make("criterion_{$criterion->id}")
                                        ->label($criterion->getTranslation('name', 'en') . " (Max: {$criterion->max_score})")
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->maxValue($criterion->max_score);
                                }
                                return $fields;
                            })
                            ->defaultItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->deletable(false),

                        Forms\Components\Textarea::make('comment')
                            ->label('Overall Comment / تعليق عام')
                            ->rows(3),
                    ];
                })
                ->action(function (array $data) {
                    $applicationId = $data['application_id'];
                    $scores = $data['scores'][0] ?? [];
                    $comment = $data['comment'] ?? null;

                    $assignedFormIds = $this->record->assignedForms()->pluck('registration_evaluation_forms.id')->toArray();
                    $criteria = RegistrationEvaluationCriterion::whereIn('registration_evaluation_form_id', $assignedFormIds)
                        ->orderBy('sort_order')
                        ->get();

                    foreach ($criteria as $criterion) {
                        $score = $scores["criterion_{$criterion->id}"] ?? 0;

                        RegistrationEvaluation::updateOrCreate(
                            [
                                'program_application_id' => $applicationId,
                                'registration_evaluator_id' => $this->record->id,
                                'registration_evaluation_criterion_id' => $criterion->id,
                            ],
                            [
                                'registration_evaluation_form_id' => $criterion->registration_evaluation_form_id,
                                'score' => (int) $score,
                                'comment' => $comment,
                            ]
                        );
                    }

                    // Update the application's final evaluation score
                    $this->updateApplicationFinalScore($applicationId);

                    Notification::make()
                        ->title('Evaluation Saved / تم حفظ التقييم')
                        ->body('Scores have been recorded successfully.')
                        ->success()
                        ->send();
                }),

            Action::make('toggleActive')
                ->label($this->record->is_active ? 'Deactivate / تعطيل' : 'Activate / تفعيل')
                ->icon($this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color($this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    Notification::make()
                        ->title('Status Updated')
                        ->success()
                        ->send();
                    $this->redirect(RegistrationEvaluatorResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    private function updateApplicationFinalScore(int $applicationId): void
    {
        $application = ProgramApplication::find($applicationId);
        if (!$application) return;

        $totalScore = RegistrationEvaluation::where('program_application_id', $applicationId)->sum('score');

        $application->update([
            'final_evaluation_score' => $totalScore,
        ]);
    }
}
