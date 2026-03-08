<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use App\Models\Program;
use App\Models\ProgramLabel;
use App\Models\EvaluationStageConfig;
use App\Models\Form;
use App\Models\FormAiScoringConfig;
use App\Models\ProjectFormConfig;
use App\Models\RegistrationEvaluationForm;
use App\Models\RegistrationFormConfig;
use App\Models\Stage;
use App\Models\TeamFormConfig;
use App\Models\Track;
use App\Models\SubTrack;
use App\Models\UserProgram;
use App\Services\ProgramApprovalService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form as FilamentForm;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Contracts\Support\Htmlable;

class ManageProgram extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = ProgramResource::class;
    protected static string $view = 'filament.pages.manage-program';

    public string $activeTab = 'setup';

    // Form data for each tab
    public ?array $overviewData = [];
    public ?array $registrationData = [];
    public ?array $teamData = [];
    public ?array $submissionEvalData = [];
    public ?array $aiScoringData = [];
    public ?array $regEvalData = [];
    public ?array $labelsData = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (!$this->record->canAccessProgram()) {
            abort(404, 'Program not found');
        }

        if ($this->record->isArchived()) {
            Notification::make()
                ->title('Archived Program')
                ->body('This program is archived. You can only view it.')
                ->warning()
                ->send();
        }

        $this->fillOverviewForm();
        $this->fillRegistrationForm();
        $this->fillTeamForm();
        $this->fillSubmissionEvalForm();
        $this->fillAiScoringForm();
        $this->fillRegEvalForm();
        $this->fillLabelsForm();
    }

    public function getTitle(): string|Htmlable
    {
        $title = $this->record->getTranslation('title', 'en');
        return "Manage: {$title}";
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Restore action for archived records
        $actions[] = Actions\Action::make('restore')
            ->label('Restore / استعادة')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Restore Program / استعادة البرنامج')
            ->modalDescription('Are you sure you want to restore this program? / هل أنت متأكد من استعادة هذا البرنامج؟')
            ->authorize(fn () => ProgramResource::canRestore($this->record))
            ->visible(fn () => $this->record->isArchived())
            ->action(function () {
                $this->record->restore();
                Notification::make()
                    ->title('Program Restored / تم استعادة البرنامج')
                    ->body('The program has been restored successfully. / تم استعادة البرنامج بنجاح.')
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.programs.index'));
            });

        // Delete action
        $actions[] = Actions\Action::make('delete')
            ->label('Delete / حذف')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize(fn () => ProgramResource::canDelete($this->record))
            ->modalHeading('Delete Program / حذف البرنامج')
            ->modalDescription('Are you sure you want to delete this program? This action will be submitted for approval. / هل أنت متأكد من حذف هذا البرنامج؟ سيتم تقديم هذا الإجراء للموافقة.')
            ->action(function () {
                $approvalService = new ProgramApprovalService();

                $result = $approvalService->processAction(
                    'delete',
                    [
                        'program_id' => $this->record->id,
                        'title' => $this->record->title,
                        'old_values' => $this->record->toArray(),
                    ],
                    $this->record->id,
                    'Program deletion request / طلب حذف البرنامج'
                );

                if ($result['success']) {
                    if ($result['requires_approval']) {
                        Notification::make()
                            ->title('Deletion Request Submitted / تم تقديم طلب الحذف')
                            ->body('Your program deletion request has been submitted for approval.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                    } else {
                        $this->record->delete();
                        Notification::make()
                            ->title('Program Deleted / تم حذف المسابقة')
                            ->body('The program has been deleted successfully.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.programs.index'));
                    }
                } else {
                    Notification::make()
                        ->title('Error / خطأ')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            });

        // Archive action (only for non-archived records)
        $actions[] = Actions\Action::make('archive')
            ->label('Archive / أرشفة')
            ->icon('heroicon-o-archive-box')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Archive Program / أرشفة البرنامج')
            ->modalDescription('Are you sure you want to archive this program? / هل أنت متأكد من أرشفة هذا البرنامج؟')
            ->authorize(fn () => ProgramResource::canArchive($this->record))
            ->visible(fn () => !$this->record->isArchived())
            ->action(function () {
                $approvalService = new ProgramApprovalService();

                $result = $approvalService->processAction(
                    'archive',
                    [
                        'is_archived' => true,
                        'program_id' => $this->record->id,
                        'title' => $this->record->title,
                        'old_values' => ['is_archived' => $this->record->is_archived ?? false],
                    ],
                    $this->record->id,
                    'Program archive request / طلب أرشفة البرنامج'
                );

                if ($result['success']) {
                    if ($result['requires_approval']) {
                        Notification::make()
                            ->title('Archive Request Submitted / تم تقديم طلب الأرشفة')
                            ->body('Your program archive request has been submitted for approval.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.my-requests.index'));
                    } else {
                        $this->record->update(['is_archived' => true]);
                        Notification::make()
                            ->title('Program Archived / تم أرشفة المسابقة')
                            ->body('The program has been archived successfully.')
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.resources.programs.index'));
                    }
                } else {
                    Notification::make()
                        ->title('Error / خطأ')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            });

        return $actions;
    }

    // ─── Overview Tab ────────────────────────────────────────────

    protected function fillOverviewForm(): void
    {
        $this->overviewForm->fill($this->record->toArray());
    }

    public function overviewForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Details')
                    ->description('Basic program information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('title.en')
                                ->label('Title (English)')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('title.ar')
                                ->label('العنوان (عربي)')
                                ->maxLength(255)
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\Select::make('type')
                            ->label('Program Type')
                            ->options([
                                'Hackathon' => 'Hackathon',
                                'Sandbox' => 'Sandbox',
                                'Idea Bank' => 'Idea Bank',
                            ])
                            ->required(),

                        Forms\Components\Group::make([
                            Forms\Components\RichEditor::make('about.en')
                                ->label('About (English)')
                                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                            Forms\Components\RichEditor::make('about.ar')
                                ->label('حول (عربي)')
                                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList'])
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\RichEditor::make('terms_and_conditions.en')
                                ->label('Terms & Conditions (English)')
                                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                            Forms\Components\RichEditor::make('terms_and_conditions.ar')
                                ->label('الشروط والأحكام (عربي)')
                                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList'])
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\FileUpload::make('banner')
                            ->label('Banner Image')
                            ->image()
                            ->directory('programs/banners')
                            ->maxSize(5120),

                        Forms\Components\Checkbox::make('is_published')
                            ->label('Published')
                            ->helperText('Make this program visible to the public.'),
                    ]),
            ])
            ->statePath('overviewData')
            ->model($this->record);
    }

    public function saveOverview(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->overviewForm->getState();

        $approvalService = new ProgramApprovalService();
        $oldValues = $this->record->only(array_keys($data));

        $actionData = array_merge($data, [
            'program_id' => $this->record->id,
            'old_values' => $oldValues,
        ]);

        $result = $approvalService->processAction(
            'update',
            $actionData,
            $this->record->id,
            'Program update request'
        );

        if ($result['success']) {
            if ($result['requires_approval']) {
                Notification::make()
                    ->title('Update Request Submitted')
                    ->body('Your update has been submitted for approval.')
                    ->success()
                    ->send();
            } else {
                $this->record->refresh();
                $this->fillOverviewForm();
                Notification::make()
                    ->title('Program Updated')
                    ->body('Program details saved successfully.')
                    ->success()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Error')
                ->body($result['message'] ?? 'An error occurred.')
                ->danger()
                ->send();
        }
    }

    // ─── Stages & Tracks Tab ─────────────────────────────────────

    public function getStages()
    {
        return $this->record->stages()->orderBy('id')->get();
    }

    public function getTracks()
    {
        return $this->record->tracks()->with('subTracks')->orderBy('order')->get();
    }

    // ─── Registration Tab (Full Schema) ────────────────────────────

    protected function fillRegistrationForm(): void
    {
        $config = $this->record->registrationFormConfig;
        if ($config) {
            $data = $config->toArray();
            // Load assessment criteria manually for the repeater
            $data['assessment_criteria'] = $config->assessmentCriteria()
                ->orderBy('sort_order')
                ->get()
                ->toArray();
            $this->registrationForm->fill($data);
        }
    }

    public function registrationForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registration Configuration')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Select::make('registration_type')
                            ->label('Registration Type')
                            ->options([
                                'individual' => 'Individual (No additional fields)',
                                'team' => 'Team',
                                'both' => 'Both Individual & Team',
                            ])
                            ->helperText('Select how participants can register.')
                            ->required()
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('Age Restrictions')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('min_age')
                                ->label('Minimum Age')
                                ->numeric()
                                ->nullable()
                                ->minValue(10)
                                ->reactive(),
                            Forms\Components\TextInput::make('max_age')
                                ->label('Maximum Age')
                                ->numeric()
                                ->nullable()
                                ->reactive(),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make('Team Registration Settings')
                    ->visible(fn ($get) => in_array($get('registration_type'), ['team', 'both']))
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('min_team_members')
                                ->label('Minimum Team Members')
                                ->default(2)
                                ->numeric()
                                ->required()
                                ->minValue(1),
                            Forms\Components\TextInput::make('max_team_members')
                                ->label('Maximum Team Members')
                                ->numeric()
                                ->nullable(),
                        ])->columns(2),

                        Forms\Components\Placeholder::make('team_fields_info')
                            ->content('The following fields will be automatically added: Team Name (required), Team Logo (optional), Team Serial Number (required).')
                            ->columnSpanFull(),
                    ]),

                // Register As Options (only if type = both)
                Forms\Components\Section::make('Register As Options')
                    ->visible(fn ($get) => $get('registration_type') === 'both')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('label_register_as.en')
                                ->label('Label - Register As (English)')
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                            Forms\Components\TextInput::make('label_register_as.ar')
                                ->label('التسمية - التسجيل ك')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('option_register_individual.en')
                                ->label('Option - Individual (English)')
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                            Forms\Components\TextInput::make('option_register_individual.ar')
                                ->label('الخيار - الفرد')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('option_register_team.en')
                                ->label('Option - Team (English)')
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                            Forms\Components\TextInput::make('option_register_team.ar')
                                ->label('الخيار - الفريق')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required(fn ($get) => $get('registration_type') === 'both'),
                        ])->columns(2),
                    ]),

                // Field Labels
                Forms\Components\Section::make('Field Labels & Text (Optional)')
                    ->visible(fn ($get) => in_array($get('registration_type'), ['team', 'both']))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('label_team_name.en')->label('Team Name Label'),
                            Forms\Components\TextInput::make('label_team_name.ar')->label('اسم الفريق (التسمية)')
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('label_team_logo.en')->label('Team Logo Label'),
                            Forms\Components\TextInput::make('label_team_logo.ar')->label('شعار الفريق')
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('label_team_serial.en')->label('Team Serial Label'),
                            Forms\Components\TextInput::make('label_team_serial.ar')->label('رقم الفريق')
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('help_team_serial.en')->label('Help Text - Serial Number'),
                            Forms\Components\TextInput::make('help_team_serial.ar')->label('الرقم التسلسلي')
                                ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        ])->columns(2),
                    ]),

                // Scoring Configuration
                Forms\Components\Section::make('Scoring Configuration')
                    ->description('Configure assessment criteria for scoring registration submissions.')
                    ->schema([
                        Forms\Components\Toggle::make('scoring_enabled')
                            ->label('Enable Scoring')
                            ->helperText('When enabled, admins will be required to enter scores when accepting submissions.')
                            ->reactive()
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('assessment_criteria')
                            ->label('Assessment Criteria')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->placeholder('e.g., "Technical Skills", "Innovation", "Feasibility"')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('max_score')
                                    ->label('Maximum Score')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(50),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->integer()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Assessment Criterion')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Criterion')
                            ->visible(fn ($get) => $get('scoring_enabled') === true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('registrationData');
    }

    public function saveRegistration(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->registrationForm->getState();
        $data['program_id'] = $this->record->id;

        $criteria = $data['assessment_criteria'] ?? [];
        unset($data['assessment_criteria']);

        $config = $this->record->registrationFormConfig;

        if ($config) {
            $config->update($data);
        } else {
            $config = RegistrationFormConfig::create($data);
        }

        // Sync assessment criteria manually
        $existingIds = $config->assessmentCriteria()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($criteria as $criterion) {
            if (!empty($criterion['id']) && in_array($criterion['id'], $existingIds)) {
                $config->assessmentCriteria()->where('id', $criterion['id'])->update([
                    'description' => $criterion['description'],
                    'max_score' => $criterion['max_score'],
                    'sort_order' => $criterion['sort_order'],
                ]);
                $keepIds[] = $criterion['id'];
            } else {
                $new = $config->assessmentCriteria()->create([
                    'description' => $criterion['description'],
                    'max_score' => $criterion['max_score'],
                    'sort_order' => $criterion['sort_order'],
                ]);
                $keepIds[] = $new->id;
            }
        }

        // Delete removed criteria
        $config->assessmentCriteria()->whereNotIn('id', $keepIds)->delete();

        $this->record->refresh();
        $this->fillRegistrationForm();

        Notification::make()
            ->title('Registration Config Saved')
            ->success()
            ->send();
    }

    // ─── Team Tab ────────────────────────────────────────────────

    protected function fillTeamForm(): void
    {
        $config = $this->record->teamFormConfig;
        if ($config) {
            $this->teamForm->fill($config->toArray());
        }
    }

    public function teamForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Size Settings')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('min_team_members')
                                ->label('Min Members')
                                ->numeric()
                                ->default(2)
                                ->minValue(2),
                            Forms\Components\TextInput::make('max_team_members')
                                ->label('Max Members')
                                ->numeric()
                                ->default(6)
                                ->minValue(2)
                                ->maxValue(10),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make('Track Rules')
                    ->schema([
                        Forms\Components\Toggle::make('allow_track_selection')
                            ->label('Allow Track/Subtrack Selection')
                            ->helperText('Team leaders can select a track during team creation.')
                            ->live(),
                        Forms\Components\Toggle::make('require_same_track')
                            ->label('Require Same Track for All Members')
                            ->helperText('All invited members must belong to the same track.')
                            ->reactive(),
                    ])->columns(2),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Toggle::make('auto_publish_teams')
                            ->label('Auto-Publish Teams')
                            ->helperText('Teams will be automatically visible in the public list.')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ])
            ->statePath('teamData');
    }

    public function saveTeam(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->teamForm->getState();
        $data['program_id'] = $this->record->id;

        $config = $this->record->teamFormConfig;

        if ($config) {
            $config->update($data);
        } else {
            TeamFormConfig::create($data);
        }

        $this->record->refresh();
        $this->fillTeamForm();

        Notification::make()
            ->title('Team Config Saved')
            ->success()
            ->send();
    }

    // ─── Unified Submission & Evaluation Tab ─────────────────────

    protected function fillSubmissionEvalForm(): void
    {
        $evalConfig = $this->record->evaluationStageConfig;

        // Load project form config (first one for this program)
        $projectConfig = ProjectFormConfig::whereHas('form', function ($q) {
            $q->where('program_id', $this->record->id);
        })->first();

        $data = [
            'project_form_id' => $projectConfig?->form_id,
            'allow_track_change' => $projectConfig?->allow_track_change ?? false,
            'evaluation_stages' => $evalConfig ? ($evalConfig->stages ?? []) : [],
            'evaluation_is_active' => $evalConfig->is_active ?? true,
        ];

        // Ensure at least one eval stage
        if (empty($data['evaluation_stages'])) {
            $data['evaluation_stages'] = [[
                'evaluation_form_id' => null,
                'apply_to_all_tracks' => true,
                'track_ids' => [],
                'submission_requirement' => 'new',
                'stage_number' => 1,
            ]];
        }

        $this->submissionEvalForm->fill($data);
    }

    public function submissionEvalForm(FilamentForm $form): FilamentForm
    {
        $projectForms = Form::where('program_id', $this->record->id)
            ->where('type', 'project-submission')
            ->where('is_archived', false)
            ->get()
            ->mapWithKeys(function ($form) {
                $name = is_array($form->name)
                    ? ($form->name['en'] ?? reset($form->name))
                    : $form->name;
                return [$form->id => $name ?: 'Untitled #' . $form->id];
            });

        $evalForms = Form::where('program_id', $this->record->id)
            ->where('type', 'evaluation')
            ->where('is_archived', false)
            ->get()
            ->mapWithKeys(function ($form) {
                $name = is_array($form->name)
                    ? ($form->name['en'] ?? reset($form->name))
                    : $form->name;
                return [$form->id => $name ?: 'Untitled #' . $form->id];
            });

        return $form
            ->schema([
                // ── Project Submission ──
                Forms\Components\Section::make('Project Submission')
                    ->icon('heroicon-o-document-text')
                    ->description('Select which form participants use to submit their project.')
                    ->schema([
                        Forms\Components\Select::make('project_form_id')
                            ->label('Project Submission Form')
                            ->options($projectForms)
                            ->placeholder($projectForms->isEmpty() ? 'No project forms created yet' : 'Select a form...')
                            ->searchable()
                            ->helperText($projectForms->isEmpty()
                                ? 'Go to the Forms section above to create a project submission form first.'
                                : 'The form participants will fill out when submitting their project.')
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('allow_track_change')
                            ->label('Allow Track Changes on Submit')
                            ->helperText('Let participants switch their track/subtrack when submitting.')
                            ->inline(false),
                    ])
                    ->columns(3),

                // ── Evaluation ──
                Forms\Components\Section::make('Evaluation Stages')
                    ->icon('heroicon-o-star')
                    ->description('Set up how projects are evaluated. Add one stage for simple judging, or multiple stages for screening rounds.')
                    ->schema([
                        Forms\Components\Toggle::make('evaluation_is_active')
                            ->label('Enable Evaluation')
                            ->default(true)
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('evaluation_stages')
                            ->label('')
                            ->visible(fn ($get) => $get('evaluation_is_active'))
                            ->schema([
                                Forms\Components\Select::make('evaluation_form_id')
                                    ->label('Evaluation Form')
                                    ->options($evalForms)
                                    ->placeholder($evalForms->isEmpty() ? 'No evaluation forms yet' : 'Select a form...')
                                    ->required()
                                    ->searchable()
                                    ->helperText(fn ($get) => $evalForms->isEmpty()
                                        ? 'Create an evaluation form in the Forms section first.'
                                        : null),

                                Forms\Components\Select::make('submission_requirement')
                                    ->label('Requires')
                                    ->options([
                                        'new' => 'New Submission',
                                        'previous' => 'Previous Stage Submission',
                                    ])
                                    ->default('new')
                                    ->required()
                                    ->helperText('Does this stage need a new submission or uses the one from a previous stage?'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => 'Stage ' . (($state['stage_number'] ?? 0)))
                            ->minItems(1)
                            ->maxItems(4)
                            ->reorderable(false)
                            ->addActionLabel('Add Evaluation Stage')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('submissionEvalData');
    }

    public function saveSubmissionEval(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->submissionEvalForm->getState();

        // ── Save Project Form Config ──
        $formId = $data['project_form_id'] ?? null;
        if ($formId) {
            $existingConfig = ProjectFormConfig::whereHas('form', function ($q) {
                $q->where('program_id', $this->record->id);
            })->first();

            if ($existingConfig) {
                $existingConfig->update([
                    'form_id' => $formId,
                    'allow_track_change' => $data['allow_track_change'] ?? false,
                ]);
            } else {
                ProjectFormConfig::create([
                    'form_id' => $formId,
                    'allow_track_change' => $data['allow_track_change'] ?? false,
                ]);
            }
        }

        // ── Save Evaluation Config ──
        $stages = $data['evaluation_stages'] ?? [];
        foreach ($stages as $i => &$stage) {
            $stage['stage_number'] = $i + 1;
        }

        $evalData = [
            'program_id' => $this->record->id,
            'number_of_stages' => count($stages),
            'stages' => $stages,
            'is_active' => $data['evaluation_is_active'] ?? true,
        ];

        $evalConfig = $this->record->evaluationStageConfig;
        if ($evalConfig) {
            $evalConfig->update($evalData);
        } else {
            EvaluationStageConfig::create($evalData);
        }

        $this->record->refresh();
        $this->fillSubmissionEvalForm();

        Notification::make()
            ->title('Submission & Evaluation Config Saved')
            ->success()
            ->send();
    }

    // ─── AI Scoring Tab ──────────────────────────────────────────

    protected function fillAiScoringForm(): void
    {
        $config = FormAiScoringConfig::whereHas('form', function ($q) {
            $q->where('program_id', $this->record->id);
        })->first();

        if ($config) {
            $data = $config->toArray();
            // Add form_type from the related form
            $data['form_type'] = $config->form?->type;
            $this->aiScoringForm->fill($data);
        }
    }

    public function aiScoringForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('AI Scoring Configuration')
                    ->icon('heroicon-o-sparkles')
                    ->description('Configure AI-powered scoring for forms in this program.')
                    ->schema([
                        Forms\Components\Select::make('form_type')
                            ->label('Form Type')
                            ->options(Form::getAvailableFormTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('form_id', null)),

                        Forms\Components\Select::make('form_id')
                            ->label('Form')
                            ->options(function (callable $get) {
                                $formType = $get('form_type');
                                if (!$formType) return [];

                                return Form::where('type', $formType)
                                    ->where('program_id', $this->record->id)
                                    ->active()
                                    ->where('is_archived', false)
                                    ->get()
                                    ->mapWithKeys(function ($form) {
                                        $name = is_array($form->name)
                                            ? ($form->name['en'] ?? reset($form->name))
                                            : $form->name;
                                        return [$form->id => $name ?: 'Untitled #' . $form->id];
                                    });
                            })
                            ->required()
                            ->live()
                            ->disabled(fn (callable $get) => !$get('form_type')),

                        Forms\Components\Textarea::make('ai_prompt')
                            ->label('AI Prompt')
                            ->placeholder('Enter AI prompt (e.g., "You are an expert on fintech…")')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('total_weight')
                            ->label('Total Weight')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(100),
                    ]),
            ])
            ->statePath('aiScoringData');
    }

    public function saveAiScoring(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->aiScoringForm->getState();
        unset($data['form_type']); // Not a db column

        $config = FormAiScoringConfig::whereHas('form', function ($q) {
            $q->where('program_id', $this->record->id);
        })->first();

        if ($config) {
            $config->update($data);
        } else {
            FormAiScoringConfig::create($data);
        }

        $this->record->refresh();
        $this->fillAiScoringForm();

        Notification::make()
            ->title('AI Scoring Config Saved')
            ->success()
            ->send();
    }

    // ─── Registration Evaluation Tab ─────────────────────────────

    protected function fillRegEvalForm(): void
    {
        $evalForm = RegistrationEvaluationForm::where('program_id', $this->record->id)->first();
        if ($evalForm) {
            $this->regEvalForm->fill($evalForm->toArray());
        }
    }

    public function regEvalForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registration Evaluation Form')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Configure evaluation criteria for registration submissions.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name.ar')
                            ->label('الاسم (عربي)')
                            ->maxLength(255)
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),

                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(3),

                        Forms\Components\Textarea::make('description.ar')
                            ->label('الوصف (عربي)')
                            ->rows(3)
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),

                        Forms\Components\TextInput::make('dimension')
                            ->label('Dimension')
                            ->helperText('e.g., Technical, Business, Innovation')
                            ->maxLength(100),

                        Forms\Components\Select::make('scoring_scale')
                            ->label('Scoring Scale')
                            ->options([
                                '1-5' => '1-5',
                                '1-10' => '1-10',
                                '1-100' => '1-100',
                            ])
                            ->default('1-10')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->statePath('regEvalData');
    }

    public function saveRegEval(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->regEvalForm->getState();
        $data['program_id'] = $this->record->id;

        $evalForm = RegistrationEvaluationForm::where('program_id', $this->record->id)->first();

        if ($evalForm) {
            $evalForm->update($data);
        } else {
            RegistrationEvaluationForm::create($data);
        }

        $this->record->refresh();
        $this->fillRegEvalForm();

        Notification::make()
            ->title('Registration Evaluation Config Saved')
            ->success()
            ->send();
    }

    // ─── Labels Tab ────────────────────────────────────────────

    protected function fillLabelsForm(): void
    {
        // Seed defaults if none exist
        if ($this->record->labels()->count() === 0) {
            ProgramLabel::seedDefaults($this->record->id);
            $this->record->refresh();
        }

        $labels = $this->record->labels()->orderBy('category')->orderBy('key')->get();
        $this->labelsForm->fill([
            'labels' => $labels->map(fn ($l) => [
                'id' => $l->id,
                'key' => $l->key,
                'category' => $l->category,
                'label_en' => $l->label_en,
                'label_ar' => $l->label_ar,
            ])->toArray(),
        ]);
    }

    public function labelsForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bilingual Labels')
                    ->icon('heroicon-o-language')
                    ->description('Customize labels displayed to participants. Changes are reflected in the frontend.')
                    ->schema([
                        Forms\Components\Repeater::make('labels')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('id'),

                                Forms\Components\TextInput::make('category')
                                    ->label('Category')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('key')
                                    ->label('Key')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('label_en')
                                    ->label('English')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('label_ar')
                                    ->label('العربية')
                                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->addable(true)
                            ->addActionLabel('Add Custom Label')
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['category'] ?? '') . '.' . ($state['key'] ?? 'new')
                            )
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('labelsData');
    }

    public function saveLabels(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }

        $data = $this->labelsForm->getState();
        $labels = $data['labels'] ?? [];

        $existingIds = $this->record->labels()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($labels as $label) {
            if (!empty($label['id']) && in_array($label['id'], $existingIds)) {
                ProgramLabel::where('id', $label['id'])->update([
                    'key' => $label['key'],
                    'category' => $label['category'],
                    'label_en' => $label['label_en'],
                    'label_ar' => $label['label_ar'] ?? '',
                ]);
                $keepIds[] = $label['id'];
            } else {
                $new = ProgramLabel::create([
                    'program_id' => $this->record->id,
                    'key' => $label['key'] ?? 'custom_' . uniqid(),
                    'category' => $label['category'] ?? 'custom',
                    'label_en' => $label['label_en'],
                    'label_ar' => $label['label_ar'] ?? '',
                ]);
                $keepIds[] = $new->id;
            }
        }

        // Delete removed labels
        $this->record->labels()->whereNotIn('id', $keepIds)->delete();

        $this->record->refresh();
        $this->fillLabelsForm();

        Notification::make()
            ->title('Labels Saved')
            ->success()
            ->send();
    }

    // ─── Helper methods ──────────────────────────────────────────

    public function getFormsList()
    {
        return Form::where('program_id', $this->record->id)
            ->where('is_archived', false)
            ->orderBy('type')
            ->orderBy('id')
            ->get();
    }

    public function getRegistrationFormsList()
    {
        return Form::where('program_id', $this->record->id)
            ->where('type', 'registration')
            ->active()
            ->where('is_archived', false)
            ->get();
    }

    public function getProjectFormsList()
    {
        return Form::where('program_id', $this->record->id)
            ->where('type', 'project-submission')
            ->active()
            ->where('is_archived', false)
            ->get();
    }

    public function getEvaluationFormsList()
    {
        return Form::where('program_id', $this->record->id)
            ->where('type', 'evaluation')
            ->active()
            ->where('is_archived', false)
            ->get();
    }

    public function getMentors()
    {
        return $this->record->mentors ?? collect();
    }

    public function getJudges()
    {
        return $this->record->judges ?? collect();
    }

    public function getEvents()
    {
        return $this->record->events ?? collect();
    }

    public function getGuidelines()
    {
        return $this->record->guidelines ?? collect();
    }

    // ─── Navigation helpers ──────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'stages' => $this->getStages(),
            'tracks' => $this->getTracks(),
            'formsList' => $this->getFormsList(),
            'registrationForms' => $this->getRegistrationFormsList(),
            'projectForms' => $this->getProjectFormsList(),
            'evaluationFormsList' => $this->getEvaluationFormsList(),
            'mentors' => $this->getMentors(),
            'judges' => $this->getJudges(),
            'events' => $this->getEvents(),
            'guidelines' => $this->getGuidelines(),
            'isArchived' => $this->record->isArchived(),
        ];
    }

    // Override to return multiple forms
    protected function getForms(): array
    {
        return [
            'overviewForm',
            'registrationForm',
            'teamForm',
            'submissionEvalForm',
            'aiScoringForm',
            'regEvalForm',
            'labelsForm',
            'stagesForm',
            'tracksForm',
        ];
    }

    // --- Stages Tab (CRUD) ---

    protected function fillStagesForm(): void
    {
        $stages = $this->record->stages()->orderBy('id')->get();
        $this->stagesForm->fill([
            'stages' => $stages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'slug' => $stage->slug,
                    'title_en' => $stage->getTranslation('title', 'en'),
                    'title_ar' => $stage->getTranslation('title', 'ar'),
                    'description_en' => $stage->getTranslation('description', 'en'),
                    'description_ar' => $stage->getTranslation('description', 'ar'),
                    'starts_at' => $stage->starts_at?->format('Y-m-d H:i:s'),
                    'ends_at' => $stage->ends_at?->format('Y-m-d H:i:s'),
                    'is_visible' => $stage->is_visible ?? false,
                    'form_id' => $stage->form_id,
                    'form_ids' => $stage->form_ids ?? [],
                ];
            })->toArray(),
        ]);
    }

    public function stagesForm(FilamentForm $form): FilamentForm
    {
        $competitionId = $this->record->id;
        return $form
            ->schema([
                Forms\Components\Section::make('Program Stages')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->description('Manage the stages of this program.')
                    ->schema([
                        Forms\Components\Repeater::make('stages')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Select::make('slug')
                                    ->label('Stage Type')
                                    ->options([
                                        'registration' => 'Registration',
                                        'team-formation' => 'Team Formation',
                                        'project-submission' => 'Project Submission',
                                        'evaluation' => 'Evaluation',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('form_id', null);
                                        $set('form_ids', []);
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('title_en')
                                        ->label('Title (English)')->required(),
                                    Forms\Components\TextInput::make('title_ar')
                                        ->label('Title (Arabic)')->required(),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\Textarea::make('description_en')
                                        ->label('Description (English)')->rows(2),
                                    Forms\Components\Textarea::make('description_ar')
                                        ->label('Description (Arabic)')->rows(2),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\DateTimePicker::make('starts_at')
                                        ->label('Starts At')->displayFormat('d/m/Y H:i')->seconds(false),
                                    Forms\Components\DateTimePicker::make('ends_at')
                                        ->label('Ends At')->displayFormat('d/m/Y H:i')->seconds(false),
                                ]),
                                Forms\Components\Toggle::make('is_visible')
                                    ->label('Visible to participants')->default(false),
                                Forms\Components\Select::make('form_id')
                                    ->label('Registration Form')
                                    ->options(function () use ($competitionId) {
                                        return \App\Models\Form::where('competition_id', $competitionId)
                                            ->where('type', 'registration')->get()
                                            ->mapWithKeys(fn ($f) => [$f->id => (is_array($f->name) ? ($f->name['en'] ?? reset($f->name)) : $f->name) ?: "Form #{$f->id}"])
                                            ->toArray();
                                    })
                                    ->visible(fn (callable $get) => $get('slug') === 'registration')
                                    ->reactive()->columnSpanFull(),
                                Forms\Components\Select::make('form_ids')
                                    ->label('Forms')
                                    ->multiple(fn (callable $get) => $get('slug') === 'project-submission' || (is_string($get('slug')) && str_starts_with($get('slug'), 'project-')))
                                    ->options(function (callable $get) use ($competitionId) {
                                        $slug = $get('slug');
                                        $query = \App\Models\Form::where('competition_id', $competitionId);
                                        if ($slug === 'evaluation') { $query->where('type', 'evaluation'); }
                                        elseif ($slug === 'project-submission') { $query->where('type', 'project'); }
                                        else { return []; }
                                        return $query->get()->mapWithKeys(fn ($f) => [$f->id => (is_array($f->name) ? ($f->name['en'] ?? reset($f->name)) : $f->name) ?: "Form #{$f->id}"])->toArray();
                                    })
                                    ->visible(fn (callable $get) => in_array($get('slug'), ['project-submission', 'evaluation']))
                                    ->reactive()->searchable()->preload()->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Stage')->maxItems(7)->reorderable(false)->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['title_en'] ?? '') ?: ($state['slug'] ?? 'New Stage'))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('stagesData');
    }

    public function saveStages(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }
        $data = $this->stagesForm->getState();
        $stages = $data['stages'] ?? [];
        $existingIds = $this->record->stages()->pluck('id')->toArray();
        $keepIds = [];
        foreach ($stages as $stageData) {
            $stageFields = [
                'title' => ['en' => $stageData['title_en'] ?? '', 'ar' => $stageData['title_ar'] ?? ''],
                'description' => ['en' => $stageData['description_en'] ?? '', 'ar' => $stageData['description_ar'] ?? ''],
                'slug' => $stageData['slug'] ?? null,
                'starts_at' => $stageData['starts_at'] ?? null,
                'ends_at' => $stageData['ends_at'] ?? null,
                'is_visible' => $stageData['is_visible'] ?? false,
                'form_id' => $stageData['form_id'] ?? null,
                'form_ids' => $stageData['form_ids'] ?? null,
            ];
            if (!empty($stageData['id']) && in_array($stageData['id'], $existingIds)) {
                $stage = Stage::find($stageData['id']);
                if ($stage) { $stage->update($stageFields); $keepIds[] = $stage->id; }
            } else {
                $stageFields['competition_id'] = $this->record->id;
                $newStage = Stage::create($stageFields);
                $keepIds[] = $newStage->id;
            }
        }
        $toDelete = array_diff($existingIds, $keepIds);
        foreach ($toDelete as $deleteId) {
            $stage = Stage::find($deleteId);
            if ($stage) {
                if ($stage->slug === 'team-formation') continue;
                if (!empty($stage->getFormIds())) continue;
                $stage->delete();
            }
        }
        $this->record->refresh();
        $this->fillStagesForm();
        Notification::make()->title('Stages Saved')->success()->send();
    }

    // --- Tracks Tab (CRUD) ---

    protected function fillTracksForm(): void
    {
        $tracks = $this->record->tracks()->with('subTracks')->orderBy('order')->get();
        $this->tracksForm->fill([
            'tracks' => $tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'name_en' => is_array($track->name) ? ($track->name['en'] ?? '') : $track->name,
                    'name_ar' => is_array($track->name) ? ($track->name['ar'] ?? '') : '',
                    'order' => $track->order ?? 0,
                    'sub_tracks' => $track->subTracks->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name_en' => is_array($sub->name) ? ($sub->name['en'] ?? '') : $sub->name,
                            'name_ar' => is_array($sub->name) ? ($sub->name['ar'] ?? '') : '',
                            'order' => $sub->order ?? 0,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ]);
    }

    public function tracksForm(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tracks & Sub-Tracks')
                    ->icon('heroicon-o-rectangle-stack')
                    ->description('Manage the competition tracks and their sub-tracks.')
                    ->schema([
                        Forms\Components\Repeater::make('tracks')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('name_en')
                                        ->label('Track Name (English)')->required(),
                                    Forms\Components\TextInput::make('name_ar')
                                        ->label('Track Name (Arabic)'),
                                    Forms\Components\TextInput::make('order')
                                        ->label('Order')->numeric()->default(0)->minValue(0),
                                ]),
                                Forms\Components\Repeater::make('sub_tracks')
                                    ->label('Sub-Tracks')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('name_en')
                                                ->label('Sub-Track Name (English)')->required(),
                                            Forms\Components\TextInput::make('name_ar')
                                                ->label('Sub-Track Name (Arabic)'),
                                            Forms\Components\TextInput::make('order')
                                                ->label('Order')->numeric()->default(0)->minValue(0),
                                        ]),
                                    ])
                                    ->addActionLabel('Add Sub-Track')->reorderable(false)->collapsible()
                                    ->itemLabel(fn (array $state): ?string => ($state['name_en'] ?? '') ?: 'New Sub-Track')
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Track')->reorderable(false)->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['name_en'] ?? '') ?: 'New Track')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('tracksData');
    }

    public function saveTracks(): void
    {
        if ($this->record->isArchived()) {
            Notification::make()->title('Cannot edit archived program')->danger()->send();
            return;
        }
        $data = $this->tracksForm->getState();
        $tracks = $data['tracks'] ?? [];
        $existingTrackIds = $this->record->tracks()->pluck('id')->toArray();
        $keepTrackIds = [];
        foreach ($tracks as $trackData) {
            $trackFields = [
                'name' => ['en' => $trackData['name_en'] ?? '', 'ar' => $trackData['name_ar'] ?? ''],
                'order' => $trackData['order'] ?? 0,
            ];
            if (!empty($trackData['id']) && in_array($trackData['id'], $existingTrackIds)) {
                $track = Track::find($trackData['id']);
                if ($track) { $track->update($trackFields); $keepTrackIds[] = $track->id; }
            } else {
                $trackFields['competition_id'] = $this->record->id;
                $track = Track::create($trackFields);
                $keepTrackIds[] = $track->id;
            }
            if ($track) {
                $existingSubIds = $track->subTracks()->pluck('id')->toArray();
                $keepSubIds = [];
                foreach (($trackData['sub_tracks'] ?? []) as $subData) {
                    $subFields = [
                        'name' => ['en' => $subData['name_en'] ?? '', 'ar' => $subData['name_ar'] ?? ''],
                        'order' => $subData['order'] ?? 0,
                    ];
                    if (!empty($subData['id']) && in_array($subData['id'], $existingSubIds)) {
                        SubTrack::where('id', $subData['id'])->update($subFields);
                        $keepSubIds[] = $subData['id'];
                    } else {
                        $subFields['track_id'] = $track->id;
                        $newSub = SubTrack::create($subFields);
                        $keepSubIds[] = $newSub->id;
                    }
                }
                $track->subTracks()->whereNotIn('id', $keepSubIds)->delete();
            }
        }
        foreach (array_diff($existingTrackIds, $keepTrackIds) as $deleteId) {
            $track = Track::find($deleteId);
            if ($track) { $track->subTracks()->delete(); $track->delete(); }
        }
        $this->record->refresh();
        $this->fillTracksForm();
        Notification::make()->title('Tracks & Sub-Tracks Saved')->success()->send();
    }

}
