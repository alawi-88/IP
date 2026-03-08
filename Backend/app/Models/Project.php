<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Scopes\ProgramApplicationScope;
use App\Traits\Program\FilterByProgram;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Forms;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;
use App\Notifications\ProjectStatusUpdated;
use App\Models\JudgeProject;
use App\Models\FormEvaluationScore;

/**
 * @method static create(array $data)
 * @method static where(string $string, $teamId)
 * @method static byProgram()
 */
//#[ScopedBy([ProgramApplicationScope::class])]
class Project extends Model
{
    use FilterByProgram, LogsActivity, HasActivityLog;

    protected $fillable = [
        'status',
        'total_score',
        'evaluation_status',
        'form_id',
        'team_id',
        'form_submissions',
        'program_id',
        'application_id',
        'is_archived',
        'archived_at',
        'type',
        'ai_evaluation_response',
        'ai_evaluated_at',
    ];

    protected $casts = [
        'form_submissions' => SchemalessAttributes::class,
        'references' => 'array',
        'documents' => 'array',
        'evaluation_status' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'type' => 'string',
        'ai_evaluation_response' => 'array',
        'ai_evaluated_at' => 'datetime',
    ];

    protected array $logFields = [
        'status',
        'total_score',
        'evaluation_status',
        'team.name',
        'program.title',
        'program_id',
        'form_submissions',
        'is_archived',
        'archived_at',
        'type',
        'ai_evaluation_response',
        'ai_evaluated_at',
    ];

    protected string $moduleName = 'Project';
    protected string $logName = 'project';

    public function scopeWithExtraAttributes(): Builder
    {
        return $this->form_submissions->modelScope();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            // Get application_id from model attribute first (for imports), then from request
            $applicationId = $project->application_id ?? request('application_id');

            if (!$applicationId) {
                return; // Skip if no application_id is available
            }

            //@TODO: convert to helper or something like that.
            $application = ProgramApplication::where('id', $applicationId)->firstOrFail();

//            try {
//                $team = Team::where('application_id', $applicationId)->firstOrFail();
//            } catch (ModelNotFoundException $e) {
//                throw new ModelNotFoundException('Team not found');
//            }

            // Only set program_id if it's not already set (for imports)
            if (!$project->program_id) {
                $project->program_id = $application->program_id;
            }
//            $project->team_id = $team->id;
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class,'application_id');
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(Judge::class, 'judge_projects')
            ->withoutGlobalScopes()
            ->withTimestamps()
            ->withPivot(['id', 'evaluation_score', 'final_comment']);
    }

    public function scopePending(): Builder
    {
        return $this->where('status', 'pending');
    }

    public function scopeQualified(): Builder
    {
        return $this->where('status', 'qualified');
    }

    public function scopeNotQualified(): Builder
    {
        return $this->where('status', 'not_qualified');
    }
    public function scopeDraft($query)
    {
        return $query->where('type', 'draft');
    }
    public function scopeSubmission($query)
    {
        return $query->where('type', 'submission');
    }
    public function scopeWinner(): Builder
    {
        return $this->where('status', 'winner');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isQualified(): bool
    {
        return $this->status === 'qualified';
    }

    public function isNotQualified(): bool
    {
        return $this->status === 'not_qualified';
    }

    public function isWinner(): bool
    {
        return $this->status === 'winner';
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): bool
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);


        return $result;
    }

    public function restore(): bool
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);


        return $result;
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function setStatusAs(string $status): self
    {
        $oldStatus = $this->status;
        $this->status = $status;
        $this->save();

        // Send notification to participant
        if ($this->team) {
            $leaderMember = $this->team?->members()->where('is_leader', true)->first();
            if ($leaderMember && $leaderMember?->participant) {
                $leaderMember?->participant?->notify(new ProjectStatusUpdated($this, $oldStatus, $status));
            }
        }else{
            $this->application?->participant?->notify(new ProjectStatusUpdated($this, $oldStatus, $status));
        }

        return $this;
    }

    public function setEvaluationStatusAs(bool $status): self
    {
        $oldStatus = $this->evaluation_status;
        $this->evaluation_status = $status;
        $this->save();

        // Send notification to participant when evaluation is completed
        if ($status && !$oldStatus) {
            if ($this->team) {
                $leaderMember = $this->team?->members()->where('is_leader', true)->first();
                if ($leaderMember && $leaderMember?->participant) {
                    $leaderMember?->participant?->notify(new \App\Notifications\ProjectEvaluationResult($this));
                }
            } else {
                $this->application?->participant?->notify(new \App\Notifications\ProjectEvaluationResult($this));
            }
        }

        return $this;
    }

    public function evaluations(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProjectEvaluation::class,
            JudgeProject::class
        );
    }

    public function updateScore(): void
    {
        // Get the last stage for this project's program
        $lastStage = $this->getLastStage();

        if (!$lastStage) {
            $this->total_score = 0;
            $this->save();
            return;
        }

        // Get FormEvaluationScore records for this project from the last stage only
        $includedScores = FormEvaluationScore::whereHas('judgeProject', function ($query) {
                $query->where('project_id', $this->id);
            })
            ->where('stage_id', $lastStage->id)
            ->where('exclude_from_calculation', false)
            ->where('is_archived', false)
            ->where('evaluation_score', '>', 0)
            ->get();

        // Group by judge_project_id to get one score per judge
        $judgeScores = $includedScores->groupBy('judge_project_id')->map(function ($scores) {
            // If a judge has multiple forms in the same stage, take the average of their scores
            return $scores->avg('evaluation_score');
        });

        // Calculate average score across all judges
        $judgeCount = $judgeScores->count();
        $totalScore = $judgeCount > 0 ? $judgeScores->avg() : 0;

        $totalScore = number_format($totalScore, 2);
        $this->total_score = $totalScore;
        $this->save();
    }

    /**
     * Get AI evaluation display criteria with description from database.
     * Similar to ProgramApplication::getAiEvaluationDisplayCriteriaAttribute
     */
    public function getAiEvaluationDisplayCriteriaAttribute(): array
    {
        $criteria = data_get($this->ai_evaluation_response, 'data.criteria', []);

        return collect($criteria)->map(function ($item) {
            $id = data_get($item, 'criteriaId');
            $description = data_get($item, 'description');
            $name = data_get($item, 'name');

            // If name or description is missing, get from database
            if ((empty($name) || empty($description)) && $id) {
                $criterion = \App\Models\FormAssessmentCriterion::find($id);
                if ($criterion) {
                    if (empty($name)) {
                        $name = $criterion->name;
                    }
                    if (empty($description)) {
                        $description = $criterion->description;
                    }
                }
            }

            $item['name'] = $name ?? '-';
            $item['description'] = $description ?? '-';
            return $item;
        })->values()->all();
    }

    /**
     * Get the last stage for this project's program
     */
    public function getLastStage()
    {
        // Get the program from the project's application
        $program = $this->application?->program;

        if (!$program) {
            return null;
        }

        // Get the last stage by ends_at date (most recent end date)
        $lastStage = Stage::where('program_id', $program->id)
            ->whereNotNull('ends_at')
            ->orderBy('ends_at', 'desc')
            ->first();

        // If no stage with ends_at, get the most recent stage by created_at
        if (!$lastStage) {
            $lastStage = Stage::where('program_id', $program->id)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $lastStage;
    }


    public static function columns(): array
    {
        $staticColumns = [
            Tables\Columns\TextColumn::make('id')->label('Submission ID'),

            Tables\Columns\TextColumn::make('stage_names')
                ->label('Stage Name')
                ->getStateUsing(function ($record) {
                    if (!$record->form || !$record->program_id) {
                        return '-';
                    }

                    // Find stages in the same program that contain this form
                    $formId = $record->form_id;
                    
                    // Get all stages for this program
                    $allStages = \App\Models\Stage::where('program_id', $record->program_id)->get();
                    
                    // Filter stages that contain this form
                    $matchingStages = $allStages->filter(function ($stage) use ($formId) {
                        // Check if form is in form_ids array
                        $formIds = $stage->getFormIds();
                        return in_array($formId, $formIds);
                    });

                    if ($matchingStages->isEmpty()) {
                        return '-';
                    }

                    // Use current locale when possible
                    $locale = app()->getLocale();
                    $titles = $matchingStages->map(function ($stage) use ($locale) {
                        // Stage title is translatable
                        if (method_exists($stage, 'getTranslation')) {
                            return $stage->getTranslation('title', $locale) ?? $stage->title;
                        }
                        return $stage->title;
                    })->filter()->unique()->values()->all();

                    return !empty($titles) ? implode(', ', $titles) : '-';
                }),

            Tables\Columns\TextColumn::make('form.name')
                ->label('Form Name')
                ->searchable()
                ->sortable()
                ->getStateUsing(function ($record) {
                    if (!$record->form) {
                        return '-';
                    }
                    $name = $record->form->name;
                    // Handle translated name (JSON format)
                    if (is_array($name)) {
                        $locale = app()->getLocale();
                        return $name[$locale] ?? $name['en'] ?? reset($name) ?? '-';
                    }
                    return $name ?? '-';
                }),

            Tables\Columns\TextColumn::make('form_submissions->project_name')
                ->label('Project Name')
                ->getStateUsing(fn($record) => $record->form_submissions['project_name'] ?? '-')
                ->searchable(query: function ($query, $search) {
                    return $query->where('form_submissions->project_name', 'like', "%{$search}%");
                })
                ->sortable(query: function ($query, $direction) {
                    return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(form_submissions, '$.project_name')) {$direction}");
                }),

            Tables\Columns\TextColumn::make('application.participant.name')
                ->label('Participant Name')
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('application.participant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(query: function ($query, $direction) {
                    return $query->join('program_applications', 'projects.application_id', '=', 'program_applications.id')
                        ->join('participants', 'program_applications.participant_id', '=', 'participants.id')
                        ->orderBy('participants.name', $direction)
                        ->select('projects.*');
                })
                ->getStateUsing(function ($record) {
                    return $record->application?->participant?->name ?? '-';
                }),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn($record) => match ($record->status) {
                    'pending' => 'primary',
                    'qualified' => 'info',
                    'not_qualified' => 'danger',
                    'winner' => 'success',
                    'approved' => 'success',
                    default => 'gray',
                })
                ->getStateUsing(fn($record) => str($record->status)->headline())
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('unread_comments')
                ->label('Unread')
                ->badge()
                ->color('danger')
                ->getStateUsing(fn($record) => $record->comments()
                    ->whereNull('user_id')
                    ->whereNotNull('author_type')
                    ->where('is_read', false)
                    ->count())
                ->toggleable(isToggledHiddenByDefault: false),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Submitted At')
                ->sortable(),

            Tables\Columns\TextColumn::make('judges')
                ->label('Assigned Judges')
                ->getStateUsing(fn($record) => $record->judges->count()),

            Tables\Columns\TextColumn::make('judges_evaluated')
                ->label('Judges Evaluated')
                ->getStateUsing(fn($record) => $record->judges()->wherePivot('evaluation_score', '!=', 0)->count()),

            Tables\Columns\IconColumn::make('evaluation_status')->boolean()->sortable(),

            Tables\Columns\TextColumn::make('ai_score')
                ->label('AI Score')
                ->getStateUsing(function ($record) {
                    $aiResponse = $record->ai_evaluation_response;
                    $aiStatus = data_get($aiResponse, 'status');
                    $hasCompletedAi = $aiStatus === 'completed' && is_array($aiResponse);

                    if (!$hasCompletedAi) {
                        return '—';
                    }

                    // AI evaluation is completed - use AI score from meta data (preferred)
                    $normalizedScore = data_get($aiResponse, 'meta.normalized_score');
                    $aiTotalScore = data_get($aiResponse, 'meta.total_score');
                    $aiMaxWeight = data_get($aiResponse, 'meta.max_weight');

                    if ($normalizedScore !== null) {
                        $totalScore = (float) $normalizedScore;
                        $maxTotalScore = (float) data_get($aiResponse, 'meta.target_total_weight', $aiMaxWeight ?? 0);
                    } elseif ($aiTotalScore !== null) {
                        $totalScore = (float) $aiTotalScore;
                        $maxTotalScore = (float) ($aiMaxWeight ?? 0);
                    } else {
                        // Last fallback: calculate from criteria if meta is not available
                        $totalScore = 0;
                        $maxTotalScore = 0;
                        $criteria = data_get($aiResponse, 'data.criteria', []);
                        foreach ($criteria as $criterion) {
                            $totalScore += (float) (data_get($criterion, 'totalScore', 0));
                            $maxTotalScore += (float) (data_get($criterion, 'maxWeight', 0));
                        }
                    }

                    // If we have maxTotalScore from AI, show as fraction
                    if ($maxTotalScore > 0) {
                        return "{$totalScore}";
                    } elseif ($totalScore > 0) {
                        return (string) $totalScore;
                    }

                    return '—';
                })
                ->badge()
                ->color('info')
                ->sortable(query: function ($query, $direction) {
                    // Sort by normalized_score or total_score from ai_evaluation_response
                    // Try normalized_score first, fallback to total_score
                    return $query->orderByRaw(
                        "COALESCE(
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_evaluation_response, '$.meta.normalized_score')) AS DECIMAL(10,2)),
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_evaluation_response, '$.meta.total_score')) AS DECIMAL(10,2)),
                            0
                        ) {$direction}"
                    );
                })
                ->toggleable(),

            Tables\Columns\TextColumn::make('judge_score')
                ->label('Judge Score')
                ->getStateUsing(function ($record) {
                    // Check if there are actual judge evaluations (FormEvaluationScore records)
                    // If no judge evaluations exist, total_score might be set by AI, so don't show it as Judge Score
                    $hasJudgeEvaluations = FormEvaluationScore::whereHas('judgeProject', function ($query) use ($record) {
                        $query->where('project_id', $record->id);
                    })
                    ->where('is_archived', false)
                    ->where('evaluation_score', '>', 0)
                    ->exists();

                    // Only show judge score if there are actual judge evaluations
                    if ($hasJudgeEvaluations && $record->total_score !== null && $record->total_score > 0) {
                        return number_format((float) $record->total_score, 2) . '%';
                    }

                    return '—';
                })
                ->badge()
                ->color('success')
                ->sortable(query: function ($query, $direction) {
                    // Sort by total_score
                    // Note: This will include projects with AI scores in total_score,
                    // but the display logic will show '—' when there are no judge evaluations
                    return $query->orderBy('total_score', $direction);
                })
                ->toggleable(),
        ];

        $dynamicColumns = [];

        $first = self::query()->whereNotNull('form_submissions')->first();

        if ($first && $first->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
            $keys = array_keys($first->form_submissions->toArray());

            $dynamicColumns = collect($keys)->map(function ($key) {
                $label = ucwords(str_replace('_', ' ', $key));

                return Tables\Columns\TextColumn::make("form_submissions_{$key}")
                    ->label($label)
                    ->getStateUsing(function ($record) use ($key) {
                        $data = $record->form_submissions->toArray();

                        $value = $data[$key] ?? '-';

                        if ($key === 'track') {
                            // Handle both ID and slug
                            $track = is_numeric($value)
                                ? Track::find($value)
                                : Track::where('slug', $value)->first();
                            return optional($track)->name ?? $value;
                        }

                        if ($key === 'sub_track') {
                            // Handle both ID and slug
                            $subTrack = is_numeric($value)
                                ? SubTrack::find($value)
                                : SubTrack::where('slug', $value)->first();
                            return optional($subTrack)->name ?? $value;
                        }

                        if (is_string($value) && preg_match('/\.(jpg|jpeg|png|pdf|docx?|xlsx?|zip)$/i', $value)) {
                            return '<a href="' . asset('storage/' . $value) . '" target="_blank" class="text-primary underline">View</a>';
                        }

                        // Handle radio button, dropdown, checkbox, and multi-select values - convert numeric values to labels
                        $formattedValue = static::formatFormFieldValueStatic($key, $value);

                        return $formattedValue ?? '-';
                    })
                    ->html()
                    ->sortable();
            })->toArray();
        }

        return [
            ...$staticColumns,
        ];
    }


    public static function details(): array
    {
        return [
            Section::make('Form Details')
                ->columns()
                ->schema(function ($record) {
                    $entries = [];

                    // Get form submissions data
                    $data = [];
                    if ($record->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
                        $data = $record->form_submissions->toArray();
                    } elseif (is_array($record->form_submissions)) {
                        $data = $record->form_submissions;
                    } elseif (is_string($record->form_submissions)) {
                        $data = json_decode($record->form_submissions, true) ?? [];
                    }

                    // Get all form fields from the project's form to display all fields, even if missing
                    $formFields = collect();
                    $allFormFields = collect(); // Store all fields including section_header and paragraph for reference
                    if ($record->form_id && $record->form) {
                        $allFormFields = $record->form->fields()->orderBy('sort')->get();
                        $formFields = $allFormFields
                            ->whereNotIn('type', ['section_header', 'paragraph']) // Skip display-only fields for main loop
                            ->values();
                    }

                    // Track which fields we've already added to avoid duplicates
                    $addedFields = [];

                    // If we have form fields, display all of them (even if missing from form_submissions)
                    if ($formFields->isNotEmpty()) {
                        foreach ($formFields as $field) {
                            $key = $field->slug;
                            $addedFields[] = $key;

                            $label = is_array($field->label)
                                ? ($field->label['en'] ?? $field->label['ar'] ?? Str::headline($key))
                                : ($field->label ?? Str::headline($key));

                            // Get value from form_submissions, or null if missing
                            $value = $data[$key] ?? null;

                            // For text area and text fields, ensure we're not accidentally using the field description/hint
                            // Check if the value matches any field metadata (which would indicate it's not user input)
                            if ($value !== null && is_string($value) && in_array($field->type, ['textarea', 'text'])) {
                                // Get all possible field metadata texts
                                $fieldLabel = is_array($field->label)
                                    ? ($field->label['en'] ?? $field->label['ar'] ?? '')
                                    : ($field->label ?? '');
                                $fieldLabelAr = is_array($field->label) ? ($field->label['ar'] ?? '') : '';
                                $fieldLabelEn = is_array($field->label) ? ($field->label['en'] ?? '') : '';

                                $fieldHint = is_array($field->hint ?? null)
                                    ? ($field->hint['en'] ?? $field->hint['ar'] ?? '')
                                    : ($field->hint ?? '');
                                $fieldHintAr = is_array($field->hint ?? null) ? ($field->hint['ar'] ?? '') : '';
                                $fieldHintEn = is_array($field->hint ?? null) ? ($field->hint['en'] ?? '') : '';

                                $fieldPlaceholder = is_array($field->placeholder ?? null)
                                    ? ($field->placeholder['en'] ?? $field->placeholder['ar'] ?? '')
                                    : ($field->placeholder ?? '');
                                $fieldPlaceholderAr = is_array($field->placeholder ?? null) ? ($field->placeholder['ar'] ?? '') : '';
                                $fieldPlaceholderEn = is_array($field->placeholder ?? null) ? ($field->placeholder['en'] ?? '') : '';

                                // Normalize strings for comparison (trim and lowercase)
                                $normalizedValue = trim(strtolower($value));
                                $fieldTexts = array_filter([
                                    trim(strtolower($fieldLabel)),
                                    trim(strtolower($fieldLabelAr)),
                                    trim(strtolower($fieldLabelEn)),
                                    trim(strtolower($fieldHint)),
                                    trim(strtolower($fieldHintAr)),
                                    trim(strtolower($fieldHintEn)),
                                    trim(strtolower($fieldPlaceholder)),
                                    trim(strtolower($fieldPlaceholderAr)),
                                    trim(strtolower($fieldPlaceholderEn)),
                                ]);

                                // If value matches any field metadata exactly, treat it as empty (not user input)
                                if (in_array($normalizedValue, $fieldTexts, true)) {
                                    $value = null;
                                }
                            }

                            // Handle track field
                            if ($key === 'track') {
                                if ($value !== null) {
                                    // Handle both ID and slug
                                    $track = is_numeric($value)
                                        ? Track::find($value)
                                        : Track::where('slug', $value)->first();
                                    $value = optional($track)->name ?? $value;
                                } else {
                                    $value = '-'; // Show empty state
                                }
                            }

                            // Handle sub_track field
                            if ($key === 'sub_track') {
                                if ($value !== null) {
                                    // Handle both ID and slug
                                    $subTrack = is_numeric($value)
                                        ? SubTrack::find($value)
                                        : SubTrack::where('slug', $value)->first();
                                    $value = optional($subTrack)->name ?? $value;
                                } else {
                                    $value = '-'; // Show empty state
                                }
                            }

                            // Check if the value looks like a file path
                            if ($value !== null && is_string($value) && Str::startsWith($value, 'uploads/files/')) {
                                $entries[] = ViewEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->view('filament.custom-entries.file-preview')
                                    ->viewData([
                                        'url' => Storage::url($value),
                                        'filename' => basename($value),
                                        'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                        'label' => $label,
                                    ]);
                            } else {
                                // Check if this is an option-based field that needs formatting
                                $needsFormatting = in_array($field->type, ['dropdown', 'multi_select', 'radio', 'rating', 'checkbox']);

                                $isArrayValue = is_array($value);
                                $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                if ($needsFormatting && ($isArrayValue || $isCommaSeparatedString)) {
                                    // Convert string to array if needed for option-based fields
                                    $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                    // Format option-based field values
                                    $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue);
                                } elseif ($needsFormatting) {
                                    // Format single option-based field value
                                    $formattedValue = static::formatFormFieldValueStatic($key, $value);
                                } else {
                                    // For text area and other non-option fields, use value as-is
                                    $formattedValue = $value;
                                }

                                // Use closure that accesses data directly to avoid binding issues
                                $fieldKey = $key; // Capture key for closure
                                $fieldType = $field->type; // Capture field type
                                $needsFormattingFlag = $needsFormatting; // Capture formatting flag
                                $fieldForClosure = $field; // Capture field object for closure
                                $entries[] = TextEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->getStateUsing(function ($record) use ($fieldKey, $fieldType, $needsFormattingFlag, $fieldForClosure) {
                                        // Get value directly from form_submissions
                                        $submissions = $record->form_submissions;
                                        $submissionData = [];
                                        if ($submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes) {
                                            $submissionData = $submissions->toArray();
                                        } elseif (is_array($submissions)) {
                                            $submissionData = $submissions;
                                        } elseif (is_string($submissions)) {
                                            $submissionData = json_decode($submissions, true) ?? [];
                                        }

                                        $actualValue = $submissionData[$fieldKey] ?? null;

                                        // If value is null or empty, return dash
                                        if ($actualValue === null || $actualValue === '') {
                                            return '-';
                                        }

                                        // For text area and text fields, check if value matches field metadata
                                        if (is_string($actualValue) && in_array($fieldType, ['textarea', 'text'])) {
                                            // Get all possible field metadata texts
                                            $fieldLabel = is_array($fieldForClosure->label)
                                                ? ($fieldForClosure->label['en'] ?? $fieldForClosure->label['ar'] ?? '')
                                                : ($fieldForClosure->label ?? '');
                                            $fieldLabelAr = is_array($fieldForClosure->label) ? ($fieldForClosure->label['ar'] ?? '') : '';
                                            $fieldLabelEn = is_array($fieldForClosure->label) ? ($fieldForClosure->label['en'] ?? '') : '';

                                            $fieldHint = is_array($fieldForClosure->hint ?? null)
                                                ? ($fieldForClosure->hint['en'] ?? $fieldForClosure->hint['ar'] ?? '')
                                                : ($fieldForClosure->hint ?? '');
                                            $fieldHintAr = is_array($fieldForClosure->hint ?? null) ? ($fieldForClosure->hint['ar'] ?? '') : '';
                                            $fieldHintEn = is_array($fieldForClosure->hint ?? null) ? ($fieldForClosure->hint['en'] ?? '') : '';

                                            $fieldPlaceholder = is_array($fieldForClosure->placeholder ?? null)
                                                ? ($fieldForClosure->placeholder['en'] ?? $fieldForClosure->placeholder['ar'] ?? '')
                                                : ($fieldForClosure->placeholder ?? '');
                                            $fieldPlaceholderAr = is_array($fieldForClosure->placeholder ?? null) ? ($fieldForClosure->placeholder['ar'] ?? '') : '';
                                            $fieldPlaceholderEn = is_array($fieldForClosure->placeholder ?? null) ? ($fieldForClosure->placeholder['en'] ?? '') : '';

                                            // Normalize strings for comparison (trim and lowercase)
                                            $normalizedValue = trim(strtolower($actualValue));
                                            $fieldTexts = array_filter([
                                                trim(strtolower($fieldLabel)),
                                                trim(strtolower($fieldLabelAr)),
                                                trim(strtolower($fieldLabelEn)),
                                                trim(strtolower($fieldHint)),
                                                trim(strtolower($fieldHintAr)),
                                                trim(strtolower($fieldHintEn)),
                                                trim(strtolower($fieldPlaceholder)),
                                                trim(strtolower($fieldPlaceholderAr)),
                                                trim(strtolower($fieldPlaceholderEn)),
                                            ]);

                                            // If value matches any field metadata exactly, return dash (not user input)
                                            if (in_array($normalizedValue, $fieldTexts, true)) {
                                                return '-';
                                            }
                                        }

                                        // For option-based fields, format the value
                                        if ($needsFormattingFlag) {
                                            $isArrayValue = is_array($actualValue);
                                            $isCommaSeparatedString = is_string($actualValue) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($actualValue));

                                            if ($isArrayValue || $isCommaSeparatedString) {
                                                $arrayValue = $isArrayValue ? $actualValue : array_map('trim', explode(',', $actualValue));
                                                $formatted = static::formatFormFieldValueStatic($fieldKey, $arrayValue);
                                                return $formatted ?? $actualValue;
                                            } else {
                                                $formatted = static::formatFormFieldValueStatic($fieldKey, $actualValue);
                                                return $formatted ?? $actualValue;
                                            }
                                        }

                                        // For text area and other text fields, return value as-is
                                        return $actualValue;
                                    });
                            }
                        }

                        // Also add any fields in form_submissions that aren't in the form fields (like project_name, track, subtrack)
                        foreach ($data as $key => $value) {
                            if (!in_array($key, $addedFields)) {
                                // Check if this is a section_header or paragraph field
                                $fieldFromForm = $allFormFields->firstWhere('slug', $key);
                                if ($fieldFromForm && in_array($fieldFromForm->type, ['section_header', 'paragraph'])) {
                                    // For display-only fields, only show if value is different from field label/hint
                                    $fieldLabel = is_array($fieldFromForm->label)
                                        ? ($fieldFromForm->label['en'] ?? $fieldFromForm->label['ar'] ?? '')
                                        : ($fieldFromForm->label ?? '');
                                    $fieldHint = is_array($fieldFromForm->hint ?? null)
                                        ? ($fieldFromForm->hint['en'] ?? $fieldFromForm->hint['ar'] ?? '')
                                        : ($fieldFromForm->hint ?? '');

                                    // Normalize for comparison
                                    $normalizedValue = trim(strtolower($value ?? ''));
                                    $normalizedLabel = trim(strtolower($fieldLabel));
                                    $normalizedHint = trim(strtolower($fieldHint));

                                    // Skip if value matches label or hint (it's just the field description, not user input)
                                    if ($normalizedValue === $normalizedLabel || $normalizedValue === $normalizedHint || $normalizedValue === '') {
                                        continue;
                                    }
                                }

                                $label = Str::headline($key);

                                if ($key === 'track') {
                                    // Handle both ID and slug
                                    $track = is_numeric($value)
                                        ? Track::find($value)
                                        : Track::where('slug', $value)->first();
                                    $value = optional($track)->name ?? $value;
                                }

                                if ($key === 'sub_track') {
                                    // Handle both ID and slug
                                    $subTrack = is_numeric($value)
                                        ? SubTrack::find($value)
                                        : SubTrack::where('slug', $value)->first();
                                    $value = optional($subTrack)->name ?? $value;
                                }

                                // Check if the value looks like a file path
                                if (is_string($value) && Str::startsWith($value, 'uploads/files/')) {
                                    $entries[] = ViewEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->view('filament.custom-entries.file-preview')
                                        ->viewData([
                                            'url' => Storage::url($value),
                                            'filename' => basename($value),
                                            'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                            'label' => $label,
                                        ]);
                                } else {
                                    // For fields not in form definition, try to determine if they need formatting
                                    // by checking if value looks like option indices
                                    $isArrayValue = is_array($value);
                                    $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                    if ($isArrayValue || $isCommaSeparatedString) {
                                        // Convert string to array if needed and format
                                        $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                        $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue);
                                    } else {
                                        // Try formatting, but if it's not an option field, it will return value as-is
                                        $formattedValue = static::formatFormFieldValueStatic($key, $value);
                                    }

                                    // Capture the formatted value to avoid closure binding issues
                                    $finalValue = $formattedValue !== null && $formattedValue !== '' ? $formattedValue : '-';
                                    $entries[] = TextEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->getStateUsing(fn () => $finalValue);
                                }
                            }
                        }
                    } else {
                        // Fallback: if no form fields found, display what's in form_submissions (original behavior)
                        foreach ($data as $key => $value) {
                            // Check if this is a section_header or paragraph field
                            if ($record->form_id && $record->form) {
                                $fieldFromForm = $record->form->fields()->where('slug', $key)->first();
                                if ($fieldFromForm && in_array($fieldFromForm->type, ['section_header', 'paragraph'])) {
                                    // For display-only fields, only show if value is different from field label/hint
                                    $fieldLabel = is_array($fieldFromForm->label)
                                        ? ($fieldFromForm->label['en'] ?? $fieldFromForm->label['ar'] ?? '')
                                        : ($fieldFromForm->label ?? '');
                                    $fieldHint = is_array($fieldFromForm->hint ?? null)
                                        ? ($fieldFromForm->hint['en'] ?? $fieldFromForm->hint['ar'] ?? '')
                                        : ($fieldFromForm->hint ?? '');

                                    // Normalize for comparison
                                    $normalizedValue = trim(strtolower($value ?? ''));
                                    $normalizedLabel = trim(strtolower($fieldLabel));
                                    $normalizedHint = trim(strtolower($fieldHint));

                                    // Skip if value matches label or hint (it's just the field description, not user input)
                                    if ($normalizedValue === $normalizedLabel || $normalizedValue === $normalizedHint || $normalizedValue === '') {
                                        continue;
                                    }
                                }
                            }

                            $label = Str::headline($key);

                            if ($key === 'track') {
                                // Handle both ID and slug
                                $track = is_numeric($value)
                                    ? Track::find($value)
                                    : Track::where('slug', $value)->first();
                                $value = optional($track)->name ?? $value;
                            }

                            if ($key === 'sub_track') {
                                // Handle both ID and slug
                                $subTrack = is_numeric($value)
                                    ? SubTrack::find($value)
                                    : SubTrack::where('slug', $value)->first();
                                $value = optional($subTrack)->name ?? $value;
                            }

                            // Check if the value looks like a file path
                            if (is_string($value) && Str::startsWith($value, 'uploads/files/')) {
                                $entries[] = ViewEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->view('filament.custom-entries.file-preview')
                                    ->viewData([
                                        'url' => Storage::url($value),
                                        'filename' => basename($value),
                                        'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                        'label' => $label,
                                    ]);
                            } else {
                                // For fallback case (no form fields), try to determine if they need formatting
                                $isArrayValue = is_array($value);
                                $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                if ($isArrayValue || $isCommaSeparatedString) {
                                    // Convert string to array if needed and format
                                    $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                    $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue);
                                } else {
                                    // Try formatting, but if it's not an option field, it will return value as-is
                                    $formattedValue = static::formatFormFieldValueStatic($key, $value);
                                }

                                // Capture the formatted value to avoid closure binding issues
                                $finalValue = $formattedValue !== null && $formattedValue !== '' ? $formattedValue : '-';
                                $entries[] = TextEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->getStateUsing(fn () => $finalValue);
                            }
                        }
                    }

                    if (empty($entries)) {
                        $entries[] = TextEntry::make('no_data')->label('No Form Data Available')->default('-');
                    }

                    return $entries;
                }),

            // AI Evaluation section (mirrors View Program Application behavior)
            Section::make('AI Evaluation')
                ->visible(fn ($record) => !empty($record->ai_evaluation_response))
                ->schema([
                    TextEntry::make('ai_evaluated_at')
                        ->label('Evaluated At')
                        ->dateTime()
                        ->default(fn ($record) => $record->ai_evaluated_at),

                    TextEntry::make('ai_evaluation_response.message')
                        ->label('AI Message')
                        ->default(fn ($record) => $record->ai_evaluation_response['message'] ?? '-')
                        ->columnSpanFull(),

                    TextEntry::make('ai_evaluation_total_score')
                        ->label('Total Score / النقاط الإجمالية')
                        ->getStateUsing(function ($record) {
                            $criteria = $record->ai_evaluation_display_criteria ?? [];
                            $totalScore = 0;
                            $totalWeight = 0;

                            foreach ($criteria as $criterion) {
                                $totalScore += (float) ($criterion['totalScore'] ?? 0);
                                $totalWeight += (float) ($criterion['maxWeight'] ?? 0);
                            }

                            if ($totalWeight > 0) {
                                return "{$totalScore} / {$totalWeight}";
                            }

                            return $totalScore > 0 ? (string) $totalScore : '-';
                        })
                        ->badge()
                        ->color('info')
                        ->size('lg')
                        ->columnSpanFull(),

                    RepeatableEntry::make('ai_evaluation_display_criteria')
                        ->label('Criteria')
                        ->schema([
                            TextEntry::make('name')->label('Criteria Name')->columnSpanFull(),
                            TextEntry::make('description')
                                ->label('Description')
                                ->columnSpanFull(),
                            TextEntry::make('instruction')->label('Instruction')->columnSpanFull(),
                            TextEntry::make('totalScore')->label('Total Score'),
                            TextEntry::make('maxWeight')->label('Max Weight'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ];
    }


    public function updateStatusForm(): array
    {
        $stageCount = 0;
        if ($this->program) {
            $stageCount = method_exists($this->program, 'stages') ? $this->program->stages()->count() : 0;
        }
        $currentStage = $this->current_stage ?? 1;

        return [
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(ProjectStatus::filteredOptions($stageCount, $currentStage))
                ->required(),
        ];
    }


    public static function updateEvaluationStatusForm(): array
    {
        return [
            Forms\Components\Select::make('evaluation_status')
                ->label('Evaluation Status')
                ->options([0 => 'Pending', 1 => 'Completed'])
                ->required(),
        ];
    }

    public static function assignToJudgesForm($record): array
    {
        if ($record instanceof Collection) {
            foreach ($record as $singleRecord) {
                $judges = Judge::whereHas('programs', function ($query) use ($singleRecord) {
                    $query->where('program_id', $singleRecord->program_id);
                })->pluck('name', 'id')->toArray();

            }
        } else {
            $judges = Judge::whereHas('programs', function ($query) use ($record) {
                $query->where('program_id', $record->program_id);
            })->pluck('name', 'id')->toArray();

        }
        return [
            Forms\Components\Select::make('judges')
                ->options($judges)
                ->multiple()
                ->maxItems(7)
                ->required()
                ->default(function () use ($record) {
                    if ($record instanceof Collection) {
                        return [];
                    }
                    return optional($record->judges)->pluck('id')->toArray();
                }),
        ];
    }

    public static function assignToCommitteeForm($record): array
    {
        $committees = Committee::byProgram()->pluck('title', 'id')->toArray();

        return [
            Forms\Components\Select::make('committees')
                ->options($committees)
                ->required(),
        ];
    }


    public function assignToJudges(array $judges): void
    {
        // Get current judges before changes
        $currentJudges = $this->judges()->pluck('judge_id')->toArray();
        $currentJudgeNames = Judge::whereIn('id', $currentJudges)->pluck('name')->toArray();

        // Determine which judges are being detached and attached
        $detachedJudges = array_diff($currentJudges, $judges);
        $attachedJudges = array_diff($judges, $currentJudges);

        // Detach judges not in the new list
        $this->judges()->whereNotIn('judge_id', $judges)->detach();

        // Attach new judges
        foreach ($judges as $judge) {
            JudgeProject::where('project_id', $this->id)->updateOrCreate(
                ['project_id' => $this->id, 'judge_id' => $judge],
                ['judge_id' => $judge]
            );
        }

        // Log changes if there are any
        if (!empty($detachedJudges) || !empty($attachedJudges)) {
            $newJudgeNames = Judge::whereIn('id', $judges)->pluck('name')->toArray();

            activity($this->logName)
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old' => [
                        'judges' => $currentJudgeNames,
                        'project_id' => $this->id,
                    ],
                    'attributes' => [
                        'judges' => $newJudgeNames,
                        'project_id' => $this->id,
                    ]
                ])
                ->event('updated')
                ->log('Updated project judge assignments');
        }
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function comments()
    {
        return $this->hasMany(ProjectComment::class);
    }

    /**
     * Format form field value - convert numeric values to labels for option-based fields
     */
    protected static function formatFormFieldValueStatic($fieldSlug, $value)
    {
        // Get the form field from database
        $formField = \App\Models\FormField::where('slug', $fieldSlug)->first();

        if (!$formField || !$formField->options) {
            // If value is an array and no field found, return formatted array
            if (is_array($value)) {
                return implode(', ', array_filter($value));
            }
            return $value;
        }

        // Check if field has options (dropdown, radio, rating, checkbox, multi_select)
        if (!in_array($formField->type, ['dropdown', 'multi_select', 'radio', 'rating', 'checkbox'])) {
            // If value is an array, format it nicely
            if (is_array($value)) {
                return implode(', ', array_filter($value));
            }
            return $value;
        }

        // Process options to handle both string and array formats
        $processedOptions = [];
        if (isset($formField->options['en']) && isset($formField->options['ar']) &&
            is_string($formField->options['en']) && is_string($formField->options['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($formField->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($formField->options['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => $enOptions[$i] ?? '',
                    'ar' => $arOptions[$i] ?? ''
                ];
            }
        } elseif (is_array($formField->options)) {
            $processedOptions = $formField->options;
        }

        // Handle array values (for checkbox and multi_select)
        if (is_array($value)) {
            $labels = [];
            $currentLang = app()->getLocale();

            foreach ($value as $val) {
                // Skip empty values
                if ($val === null || $val === '') {
                    continue;
                }

                // Handle numeric values (index-based)
                if (is_numeric($val)) {
                    $index = (int)$val - 1; // Convert to 0-based index
                    if (isset($processedOptions[$index])) {
                        $option = $processedOptions[$index];
                        if (is_array($option)) {
                            // Return the appropriate language value
                            $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                            if ($label) {
                                $labels[] = $label;
                            }
                        } elseif (is_string($option)) {
                            $labels[] = $option;
                        }
                    }
                } else {
                    // Handle string values - try to find matching option by value or label
                    $found = false;
                    foreach ($processedOptions as $option) {
                        if (is_array($option)) {
                            $optionValue = $option['value'] ?? null;
                            $optionLabelEn = $option['en'] ?? $option['label']['en'] ?? null;
                            $optionLabelAr = $option['ar'] ?? $option['label']['ar'] ?? null;

                            if ($optionValue == $val || $optionLabelEn == $val || $optionLabelAr == $val) {
                                $label = $currentLang === 'ar' ? ($optionLabelAr ?? $optionLabelEn ?? '') : ($optionLabelEn ?? $optionLabelAr ?? '');
                                if ($label) {
                                    $labels[] = $label;
                                }
                                $found = true;
                                break;
                            }
                        } elseif ($option == $val) {
                            $labels[] = $option;
                            $found = true;
                            break;
                        }
                    }

                    // If not found in options, use the value as is
                    if (!$found) {
                        $labels[] = $val;
                    }
                }
            }

            return implode(', ', $labels);
        }

        // Handle single numeric value (for dropdown, radio, rating)
        if (is_numeric($value)) {
            $index = (int)$value - 1; // Convert to 0-based index
            if (isset($processedOptions[$index])) {
                $option = $processedOptions[$index];
                if (is_array($option)) {
                    // Return the appropriate language value
                    $currentLang = app()->getLocale();
                    return $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                } elseif (is_string($option)) {
                    return $option;
                }
            }
        }

        // Return value as is if it's a string that doesn't match any option
        return $value;
    }

}
