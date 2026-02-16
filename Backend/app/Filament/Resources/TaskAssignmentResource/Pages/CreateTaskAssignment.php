<?php

namespace App\Filament\Resources\TaskAssignmentResource\Pages;

use App\Filament\Resources\TaskAssignmentResource;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\Stage;
use App\Models\TaskAssignment;
use App\Models\TaskTemplate;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskAssignment extends CreateRecord
{
    protected static string $resource = TaskAssignmentResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Task Assignment / تعيين مهمة')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('competition_id')
                        ->label('Program / البرنامج')
                        ->options(fn () => Competition::active()->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('title', 'en')]))
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set) {
                            $set('task_template_id', null);
                            $set('stage_id', null);
                            $set('team_id', null);
                            $set('participant_id', null);
                        }),

                    Forms\Components\Select::make('task_template_id')
                        ->label('From Template / من قالب')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            if (!$competitionId) return [];
                            return TaskTemplate::where('competition_id', $competitionId)
                                ->where('is_archived', false)
                                ->get()
                                ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslation('title', 'en')]);
                        })
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, callable $get, $state) {
                            if ($state) {
                                $template = TaskTemplate::find($state);
                                if ($template) {
                                    $set('title.en', $template->getTranslation('title', 'en'));
                                    $set('title.ar', $template->getTranslation('title', 'ar'));
                                    $set('description.en', $template->getTranslation('description', 'en'));
                                    $set('description.ar', $template->getTranslation('description', 'ar'));
                                    $set('instructions.en', $template->getTranslation('instructions', 'en'));
                                    $set('instructions.ar', $template->getTranslation('instructions', 'ar'));
                                }
                            }
                        })
                        ->helperText('Optional: Pre-fill from an existing template'),

                    Forms\Components\Select::make('stage_id')
                        ->label('Stage / المرحلة')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            if (!$competitionId) return [];
                            return Stage::where('competition_id', $competitionId)
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->getTranslation('title', 'en') ?? $s->title]);
                        })
                        ->searchable(),

                    Forms\Components\Select::make('assignment_type')
                        ->label('Assignment Type / نوع التعيين')
                        ->options([
                            'team' => 'Specific Team',
                            'participant' => 'Specific Participant',
                            'all' => 'All Participants',
                        ])
                        ->required()
                        ->reactive()
                        ->default('team'),

                    Forms\Components\Select::make('team_id')
                        ->label('Team / الفريق')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            if (!$competitionId) return [];
                            return Team::whereHas('application', fn ($q) => $q->where('competition_id', $competitionId))
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn (callable $get) => $get('assignment_type') === 'team')
                        ->required(fn (callable $get) => $get('assignment_type') === 'team'),

                    Forms\Components\Select::make('participant_id')
                        ->label('Participant / المشارك')
                        ->options(function (callable $get) {
                            $competitionId = $get('competition_id');
                            if (!$competitionId) return [];
                            return Participant::whereHas('competitionApplications', fn ($q) => $q->where('competition_id', $competitionId)->where('status', 'approved'))
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn (callable $get) => $get('assignment_type') === 'participant')
                        ->required(fn (callable $get) => $get('assignment_type') === 'participant'),

                    Forms\Components\TextInput::make('title.en')
                        ->label('Task Title (English)')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('title.ar')
                        ->label('عنوان المهمة (عربي)')
                        ->maxLength(255)
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\RichEditor::make('description.en')
                        ->label('Description (English)')
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('description.ar')
                        ->label('الوصف (عربي)')
                        ->columnSpanFull()
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\RichEditor::make('instructions.en')
                        ->label('Instructions (English)')
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('instructions.ar')
                        ->label('التعليمات (عربي)')
                        ->columnSpanFull()
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date / تاريخ الاستحقاق')
                        ->required()
                        ->minDate(now()),

                    Forms\Components\Select::make('allowed_file_formats')
                        ->label('Allowed File Formats')
                        ->multiple()
                        ->options([
                            'pdf' => 'PDF',
                            'doc' => 'DOC',
                            'docx' => 'DOCX',
                            'xls' => 'XLS',
                            'xlsx' => 'XLSX',
                            'ppt' => 'PPT',
                            'pptx' => 'PPTX',
                            'jpg' => 'JPG',
                            'png' => 'PNG',
                            'zip' => 'ZIP',
                            'mp4' => 'MP4',
                        ]),

                    Forms\Components\TextInput::make('max_file_size_mb')
                        ->label('Max File Size (MB)')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(500),

                    Forms\Components\Textarea::make('assignment_notes.en')
                        ->label('Notes to Assignee (English)')
                        ->rows(2),

                    Forms\Components\Textarea::make('assignment_notes.ar')
                        ->label('ملاحظات للمُعيَّن (عربي)')
                        ->rows(2)
                        ->extraFieldWrapperAttributes(['class' => 'text-right']),

                    Forms\Components\Hidden::make('assigned_by')
                        ->default(fn () => auth()->id()),

                    Forms\Components\Hidden::make('status')
                        ->default(TaskAssignment::STATUS_NOT_STARTED),
                ]),
        ]);
    }

    protected function afterCreate(): void
    {
        // Send task assignment notification
        try {
            $assignment = $this->record;
            if ($assignment->assignment_type === 'team' && $assignment->team) {
                // Notify all team members
                $members = $assignment->team->members ?? collect();
                foreach ($members as $member) {
                    if ($member->participant) {
                        $member->participant->notify(new \App\Notifications\Participant\TaskAssignedNotification($assignment));
                    }
                }
            } elseif ($assignment->assignment_type === 'participant' && $assignment->participant) {
                $assignment->participant->notify(new \App\Notifications\Participant\TaskAssignedNotification($assignment));
            }
        } catch (\Exception $e) {
            // Notification failure should not block task creation
            \Log::warning('Task assignment notification failed: ' . $e->getMessage());
        }
    }
}
