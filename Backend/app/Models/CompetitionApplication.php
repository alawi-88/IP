<?php

namespace App\Models;

use App\Traits\Competition\FilterByCompetition;
use App\Traits\CompetitionApplication\ManageStatus;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Tables;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;
use Illuminate\Support\Str;
use App\Models\FormAiScoringConfig;

/**
 * @method static create(mixed $validated)
 * @method static where(string $string, $id)
 * @method static pending()
 * @method static approved()
 * @method static rejected()
 * @method static count()
 * @method static findOrFail(mixed $request)
 * @method static byCompetition()
 */
class CompetitionApplication extends Model
{
    use ManageStatus, FilterByCompetition, LogsActivity, HasActivityLog;

    protected $with = ['competition', 'participant'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'competition_id',
        'form_id',
        'form_submissions',
        'status',
        'participant_id',
        'registered_as',
        'has_team',
        'team_name',
        'team_logo',
        'team_serial',
        'is_archived',
        'archived_at',
        'type',
        'assessment_scores',
        'total_score',
        'ai_evaluation_response',
        'ai_evaluated_at',
    ];

    protected $casts = [
        'form_submissions' => SchemalessAttributes::class,
        'has_team' => 'boolean',
        'team_serial' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'type' => 'string',
        'assessment_scores' => 'array',
        'total_score' => 'integer',
        'ai_evaluation_response' => 'array',
        'ai_evaluated_at' => 'datetime',
    ];

    protected array $logFields = [
        'competition.title',
        'competition_id',
        'participant.name',
        'status',
        'form_submissions',
        'is_archived',
        'archived_at',
        'type',
        'assessment_scores',
        'total_score',
        'ai_evaluation_response',
        'ai_evaluated_at',
    ];

    protected string $moduleName = 'Competition Application';
    protected string $logName = 'competition_application';

    /**
     * Enriched criteria from AI response with description lookup via criteriaId.
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


    public function scopeWithExtraAttributes(): Builder
    {
        return $this->form_submissions->modelScope();
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
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
        // Check if there's already an active application for the same participant and competition
        $existingActiveApplication = CompetitionApplication::where('participant_id', $this->participant_id)
            ->where('competition_id', $this->competition_id)
            ->where('is_archived', false)
            ->where('id', '!=', $this->id)
            ->first();

        if ($existingActiveApplication) {
            throw new \Exception(__('application_archive.cannot_restore_duplicate_application'));
        }

        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);


        return $result;
    }
    public function scopeSubmission($query)
    {
        return $query->where('type', 'submission');
    }

    /**
     * Set assessment scores and calculate total score.
     *
     * @param array $scores Array of criterion_id => score pairs
     * @return void
     */
    public function setAssessmentScores(array $scores): void
    {
        $this->assessment_scores = $scores;
        $this->total_score = array_sum($scores);
        $this->save();
    }

    /**
     * Persist AI evaluation response with derived score metadata.
     */
    public function applyAiEvaluationResponse(
        array $response,
        string $status = 'completed',
        ?string $message = null,
        ?FormAiScoringConfig $config = null,
        ?Collection $criteria = null
    ): void {
        $payload = $response;
        $payload['status'] = $status;

        if ($message) {
            $payload['message'] = $message;
        } elseif (!isset($payload['message'])) {
            $payload['message'] = null;
        }

        $meta = $this->summarizeAiEvaluation($payload, $config, $criteria);
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        $this->update([
            'ai_evaluation_response' => $payload,
            'ai_evaluated_at' => $status === 'completed' ? now() : null,
        ]);
    }

    /**
     * Summarize AI evaluation totals and normalization based on configured weights.
     */
    protected function summarizeAiEvaluation(
        array $response,
        ?FormAiScoringConfig $config = null,
        ?Collection $criteria = null
    ): array {
        $criteriaData = collect(data_get($response, 'data.criteria', []));
        $criteriaById = $criteria?->keyBy('id') ?? collect();

        $totalScore = $criteriaData->sum(fn ($criterion) => (float) data_get($criterion, 'totalScore', 0));

        $maxWeight = $criteriaData->sum(function ($criterion) use ($criteriaById) {
            $weight = data_get($criterion, 'maxWeight');
            if ($weight === null) {
                $id = data_get($criterion, 'criteriaId');
                if ($id && $criteriaById->has($id)) {
                    return (float) $criteriaById->get($id)->weight;
                }
            }
            return (float) ($weight ?? 0);
        });

        $config ??= FormAiScoringConfig::where('form_id', $this->form_id)->first();
        $targetTotalWeight = $config?->total_weight ?? $maxWeight;

        $normalizedScore = $maxWeight > 0
            ? round(($totalScore / $maxWeight) * $targetTotalWeight, 2)
            : null;

        return [
            'total_score' => $totalScore,
            'max_weight' => $maxWeight,
            'target_total_weight' => $targetTotalWeight,
            'normalized_score' => $normalizedScore,
        ];
    }

    /**
     * Status helper for AI evaluation.
     */
    public function getAiEvaluationStatusAttribute(): string
    {
        return data_get($this->ai_evaluation_response, 'status')
            ?? (!empty($this->ai_evaluation_response) ? 'completed' : 'pending');
    }

    public function hasCompletedAiEvaluation(): bool
    {
        return $this->ai_evaluation_status === 'completed';
    }

    /**
     * Total AI score returned (sum of weighted criteria).
     */
    public function getAiEvaluationTotalScoreAttribute(): float
    {
        $response = $this->ai_evaluation_response;

        if (empty($response) || !is_array($response)) {
            return 0.0;
        }

        $metaScore = data_get($response, 'meta.total_score');
        if ($metaScore !== null) {
            return (float) $metaScore;
        }

        return (float) $this->summarizeAiEvaluation($response)['total_score'];
    }

    /**
     * Maximum possible AI score (sum of criteria weights).
     */
    public function getAiEvaluationMaxWeightAttribute(): float
    {
        $response = $this->ai_evaluation_response;

        if (empty($response) || !is_array($response)) {
            return 0.0;
        }

        $metaWeight = data_get($response, 'meta.max_weight');
        if ($metaWeight !== null) {
            return (float) $metaWeight;
        }

        return (float) $this->summarizeAiEvaluation($response)['max_weight'];
    }

    /**
     * Normalized AI score respecting configured total weight.
     */
    public function getAiEvaluationNormalizedScoreAttribute(): ?float
    {
        $response = $this->ai_evaluation_response;

        if (empty($response) || !is_array($response)) {
            return null;
        }

        $metaScore = data_get($response, 'meta.normalized_score');
        if ($metaScore !== null) {
            return (float) $metaScore;
        }

        return $this->summarizeAiEvaluation($response)['normalized_score'] ?? null;
    }

    /**
     * Combined registration score (manual + AI when available).
     */
    public function getRegistrationTotalScoreAttribute(): float
    {
        $baseScore = (float) ($this->total_score ?? 0);

        if (!$this->hasCompletedAiEvaluation()) {
            return $baseScore;
        }

        $aiScore = $this->ai_evaluation_normalized_score ?? $this->ai_evaluation_total_score ?? 0;

        return $baseScore + (float) $aiScore;
    }

    /**
     * Registration score breakdown for API consumers.
     */
    public function getRegistrationScoreBreakdownAttribute(): array
    {
        return [
            'assessment_score' => (float) ($this->total_score ?? 0),
            'ai_score' => $this->hasCompletedAiEvaluation()
                ? (float) ($this->ai_evaluation_normalized_score ?? $this->ai_evaluation_total_score ?? 0)
                : 0.0,
            'ai_status' => $this->ai_evaluation_status,
            'total' => $this->registration_total_score,
        ];
    }

    /**
     * Get the registration form config for this application.
     */
    public function getRegistrationFormConfig()
    {
        return RegistrationFormConfig::where('competition_id', $this->competition_id)
            ->active()
            ->first();
    }

    /**
     * Check if scoring is enabled for this application's competition.
     */
    public function hasScoringEnabled(): bool
    {
        $config = $this->getRegistrationFormConfig();
        return $config && $config->scoring_enabled;
    }

    /**
     * Get assessment criteria for this application.
     */
    public function getAssessmentCriteria()
    {
        $config = $this->getRegistrationFormConfig();
        if (!$config) {
            return collect();
        }
        return $config->assessmentCriteria;
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false)
            ->whereHas('competition', function ($q) {
                $q->where('is_archived', false);
            });
    }
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CompetitionApplication $application) {
            // Only set registered_as if not already set (to avoid overriding values from controller)
            if (empty($application->registered_as)) {
                // Convert boolean has_team to string 'team' or 'individual'
                $application->registered_as = $application->has_team ? 'team' : 'individual';
            }
            $application->participant_id = auth()->id();
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'application_id')->withoutGlobalScopes()->active();
    }

    public function form()
    {
        return $this->belongsTo(Form::class,'form_id');
    }

    public function comments()
    {
        return $this->hasMany(ApplicationComment::class, 'application_id');
    }

    /**
     * Get projects associated with this application.
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'application_id');
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('rank')
                ->label('Rank')
                ->getStateUsing(function ($record) {
                    // Only calculate rank for submission type applications
                    if ($record->type !== 'submission') {
                        return __('leaderboard.rank_not_available');
                    }

                    // Calculate rank among all non-archived submissions in the same competition that have scores
                    // Include both approved and pending applications to show relative ranking
                    $baseQuery = self::query()
                        ->where('competition_id', $record->competition_id)
                        ->where('is_archived', false)
                        ->where('type', 'submission');

                    $applications = $baseQuery
                        ->select('id', 'total_score', 'ai_evaluation_response', 'status')
                        ->get()
                        ->map(function ($application) {
                            /** @var self $application */
                            // Calculate score the same way as the Scores column
                            $baseScore = (float) ($application->total_score ?? 0);
                            
                            // Calculate AI score - use the same logic as Scores column
                            $aiScore = 0;
                            if (!empty($application->ai_evaluation_response)) {
                                $aiResponse = $application->ai_evaluation_response;
                                
                                // Try multiple methods to get AI score (same as Scores column logic)
                                // Method 1: Use normalized score from meta
                                $normalizedScore = data_get($aiResponse, 'meta.normalized_score');
                                if ($normalizedScore !== null) {
                                    $aiScore = (float) $normalizedScore;
                                } else {
                                    // Method 2: Use total score from meta
                                    $totalScore = data_get($aiResponse, 'meta.total_score');
                                    if ($totalScore !== null) {
                                        $aiScore = (float) $totalScore;
                                    } else {
                                        // Method 3: Calculate from criteria (same as Scores column)
                                        $criteria = $application->ai_evaluation_display_criteria ?? [];
                                        foreach ($criteria as $criterion) {
                                            $aiScore += (float) ($criterion['totalScore'] ?? 0);
                                        }
                                        
                                        // Fallback: if display_criteria not available, use data.criteria
                                        if ($aiScore == 0) {
                                            $criteriaData = collect(data_get($aiResponse, 'data.criteria', []));
                                            $aiScore = (float) $criteriaData->sum(fn ($criterion) => (float) data_get($criterion, 'totalScore', 0));
                                        }
                                    }
                                }
                            }
                            
                            $totalScore = $baseScore + $aiScore;
                            
                            // Only include applications that have a score > 0
                            if ($totalScore <= 0) {
                                return null;
                            }
                            
                            return [
                                'id' => $application->id,
                                'score' => $totalScore,
                            ];
                        })
                        ->filter() // Remove null entries (applications with no score)
                        ->sortByDesc('score')
                        ->values();

                    // If no applications with scores found, return not available
                    if ($applications->isEmpty()) {
                        return __('leaderboard.rank_not_available');
                    }

                    // Calculate rank properly handling ties
                    $rank = null;
                    $currentRank = 1;
                    $previousScore = null;

                    foreach ($applications as $index => $app) {
                        // If score is different from previous, update rank
                        if ($previousScore !== null && $app['score'] < $previousScore) {
                            // Rank is the position in the list (1-based index)
                            $currentRank = $index + 1;
                        }
                        // If score is the same as previous, keep the same rank (ties)

                        // Compare IDs as integers to avoid type mismatch issues
                        if ((int) $app['id'] === (int) $record->id) {
                            $rank = $currentRank;
                            break;
                        }

                        $previousScore = $app['score'];
                    }

                    return $rank ?? __('leaderboard.rank_not_available');
                })
                ->toggleable(isToggledHiddenByDefault: false),
            Tables\Columns\TextColumn::make('id')->searchable()->sortable(),

            Tables\Columns\TextColumn::make('participant.name')
                ->label('Participant Name')
                ->getStateUsing(function ($record) {
                    // First try to get from participant relationship
                    if ($record->participant) {
                        return $record->participant->name ?? '-';
                    }
                    // Fallback to form_submissions if participant not loaded
                    if (isset($record->form_submissions['participant_name'])) {
                        return $record->form_submissions['participant_name'];
                    }
                    // Try to construct from first_name and last_name
                    $firstName = $record->form_submissions['first_name'] ?? '';
                    $lastName = $record->form_submissions['last_name'] ?? '';
                    if ($firstName || $lastName) {
                        return trim($firstName . ' ' . $lastName) ?: '-';
                    }
                    return '-';
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('participant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })->orWhere('form_submissions->participant_name', 'like', "%{$search}%")
                      ->orWhere('form_submissions->first_name', 'like', "%{$search}%");
                })
                ->sortable(query: function ($query, $direction) {
                    return $query->join('participants', 'competition_applications.participant_id', '=', 'participants.id')
                        ->orderBy('participants.name', $direction)
                        ->select('competition_applications.*');
                }),

            Tables\Columns\TextColumn::make('participant.email')
                ->label('Participant Email')
                ->getStateUsing(function ($record) {
                    // First try to get from participant relationship
                    if ($record->participant) {
                        return $record->participant->email ?? '-';
                    }
                    // Fallback to form_submissions if participant not loaded
                    return $record->form_submissions['participant_email']
                        ?? $record->form_submissions['email']
                        ?? '-';
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('participant', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%");
                    })->orWhere('form_submissions->participant_email', 'like', "%{$search}%")
                      ->orWhere('form_submissions->email', 'like', "%{$search}%");
                })
                ->sortable(query: function ($query, $direction) {
                    return $query->join('participants', 'competition_applications.participant_id', '=', 'participants.id')
                        ->orderBy('participants.email', $direction)
                        ->select('competition_applications.*');
                }),

            Tables\Columns\TextColumn::make('track')
                ->label('Track')
                ->getStateUsing(function ($record) {
                    // Load team relationship if not loaded and has_team is true
                    if ($record->has_team && !$record->relationLoaded('team')) {
                        $record->load('team.track');
                    }

                    // First check if there's a team with track
                    if ($record->team && $record->team->track_id) {
                        return $record->team->track->name ?? '—';
                    }
                    // Then check form_submissions
                    $trackId = $record->form_submissions['track'] ?? null;
                    if ($trackId) {
                        return \App\Models\Track::find($trackId)?->name ?? '—';
                    }
                    return '—';
                }),

            Tables\Columns\TextColumn::make('sub_track')
                ->label('Sub-Track')
                ->getStateUsing(function ($record) {
                    // Load team relationship if not loaded and has_team is true
                    if ($record->has_team && !$record->relationLoaded('team')) {
                        $record->load('team.subTrack');
                    }

                    // First check if there's a team with sub_track
                    if ($record->team && $record->team->sub_track_id) {
                        return $record->team->subTrack->name ?? '—';
                    }
                    // Then check form_submissions
                    $subTrackId = $record->form_submissions['sub_track'] ?? null;
                    if ($subTrackId) {
                        return \App\Models\SubTrack::find($subTrackId)?->name ?? '—';
                    }
                    return '—';
                }),


            Tables\Columns\IconColumn::make('has_team')
                ->boolean()
                ->getStateUsing(function ($record) {
                    // Get value from direct column first
                    $value = $record->has_team;

                    // If null, try form_submissions
                    if ($value === null) {
                        $value = data_get($record->form_submissions, 'has_team', false);
                    }

                    // Ensure boolean value
                    return (bool) $value;
                }),

            Tables\Columns\TextColumn::make('registered_as')
                ->badge()
                ->getStateUsing(function ($record) {
                    // Get value from direct column first
                    $value = $record->registered_as;

                    // Handle boolean values (legacy data that might have boolean instead of string)
                    if (is_bool($value)) {
                        $value = $value ? 'team' : 'individual';
                    }

                    // If null, empty, or invalid, try form_submissions
                    if (empty($value) || !in_array($value, ['team', 'individual'])) {
                        $value = data_get($record->form_submissions, 'register_as');
                    }

                    // If still null, empty, or invalid, check has_team as fallback
                    if (empty($value) || !in_array($value, ['team', 'individual'])) {
                        $value = $record->has_team ? 'team' : 'individual';
                    }

                    return $value;
                })
                ->colors([
                    'success' => 'team',
                    'info'    => 'individual',
                    'gray'    => fn ($state) => ! in_array($state, ['team', 'individual']),
                ]),



            Tables\Columns\TextColumn::make('status')
                ->getStateUsing(fn($record) => str($record->status)->ucfirst())
                ->badge()
                ->color(fn($record) => match ($record->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                })->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Submitted At')
                ->sortable()
                ->searchable(),

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
            
            Tables\Columns\TextColumn::make('ai_score')
                ->label('AI Score')
                ->getStateUsing(function ($record) {
                    $aiScore = 0;
                    if (!empty($record->ai_evaluation_response)) {
                        $criteria = $record->ai_evaluation_display_criteria ?? [];
                        foreach ($criteria as $criterion) {
                            $aiScore += (float) ($criterion['totalScore'] ?? 0);
                        }
                    }
                    return $aiScore > 0 ? (string) $aiScore : '—';
                })
                ->badge()
                ->color('info')
                ->sortable(query: function ($query, $direction) {
                    // Sort by AI score if available, otherwise 0
                    // If meta.normalized_score exists, use it. Otherwise, sum criteria
                    return $query->orderByRaw(
                        "CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_evaluation_response, '$.meta.normalized_score')) AS DECIMAL(10,2)) " . $direction
                    );
                })
                ->toggleable(),

            Tables\Columns\TextColumn::make('admin_score')
                ->label('Admin Score')
                ->getStateUsing(function ($record) {
                    $manualScore = (float) ($record->total_score ?? 0);
                    return $manualScore > 0 ? (string) $manualScore : '—';
                })
                ->badge()
                ->color('info')
                ->sortable(query: function ($query, $direction) {
                    return $query->orderBy('total_score', $direction);
                })
                ->toggleable(),

            Tables\Columns\TextColumn::make('total_score_combined')
                ->label('Total Score')
                ->getStateUsing(function ($record) {
                    $aiScore = 0;
                    if (!empty($record->ai_evaluation_response)) {
                        $criteria = $record->ai_evaluation_display_criteria ?? [];
                        foreach ($criteria as $criterion) {
                            $aiScore += (float) ($criterion['totalScore'] ?? 0);
                        }
                    }
                    $manualScore = (float) ($record->total_score ?? 0);
                    $totalScore = $manualScore + $aiScore;
                    return $totalScore > 0 ? (string) $totalScore : '—';
                })
                ->badge()
                ->color('info')
                // Default sort by combined total: manual + AI
                ->sortable(query: function ($query, $direction) {
                    // Combined total: manual + AI score
                    // Fallback to manual score if AI is missing.
                    // This simple version only sorts by total_score for now for performance.
                    // Advanced: You could use a subquery or accessor if you want dynamic AI sum in DB.
                    return $query->orderBy('total_score', $direction);
                })
                ->toggleable(),
        ];
    }

    public static function details(): array
    {
        return [
            // Basic Info
            Section::make('Basic Information')
                ->columns(4)
                ->schema([
                    TextEntry::make('id')
                        ->label('Application ID')
                        ->getStateUsing(fn($record) => str($record->id)->prepend('#')),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($record) => match ($record->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'pending' => 'warning',
                            default => 'gray',
                        })
                        ->getStateUsing(fn($record) => str($record->status)->ucfirst()),

                    TextEntry::make('created_at')->label('Created At'),
                    TextEntry::make('updated_at')->label('Last Updated'),
                ]),


            // Competition Info
            Section::make('Program Info')
                ->schema([
                    TextEntry::make('competition.title')->label('Program'),
                ]),

            // Participant (from relationship or form_submissions so imported applications show correctly)
            Section::make('Participant')
                ->columns(2)
                ->schema([
                    TextEntry::make('participant.name')
                        ->label('Participant Name')
                        ->getStateUsing(fn($record) => $record->participant?->name ?? $record->form_submissions['participant_name'] ?? '—'),
                    TextEntry::make('participant.email')
                        ->label('Participant Email')
                        ->getStateUsing(fn($record) => $record->participant?->email ?? $record->form_submissions['participant_email'] ?? '—'),
                ]),

            // Assessment Scores (if scoring is enabled and scores exist)
            Section::make('Assessment Scores / نقاط التقييم')
                ->visible(fn($record) => $record->hasScoringEnabled() && $record->assessment_scores !== null && !empty($record->assessment_scores))
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
                ->columns(2),

            // Team Info (if applicable)
            Section::make('Team')
                ->hidden(fn($record) => !$record->has_team)
                ->columns(3)
                ->schema([
                    TextEntry::make('team.name')->label('Team Name'),

                    ViewEntry::make('team.logo')
                        ->label('Team Logo')
                        ->view('filament.custom-entries.file-preview')
                        ->viewData(fn($record) => [
                            'url' => $record->team?->logo ?  Storage::url(ltrim(str_replace(Storage::url('/'), '', $record->team->logo), '/')) : null,
                            'filename' => $record->team?->logo ? basename($record->team->logo) : '',
                            'isImage' => $record->team?->logo ? preg_match('/\.(jpg|jpeg|png|webp)$/i', $record->team->logo) : false,
                            'label' => 'Team Logo',
                        ]),

                    TextEntry::make('serial_numbers')
                        ->label('Team Member Serials')
                        ->getStateUsing(fn($record) => $record->team?->members?->pluck('participant.serial_number')->join(', ') ?? '-'),
                ]),

            Section::make('Team Members')
                ->hidden(fn($record) => !$record->has_team)
                ->schema([
                    RepeatableEntry::make('team.members')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('participant.name')->label('Name'),
                            TextEntry::make('participant.email')->label('Email'),
                            IconEntry::make('is_leader')->boolean()->label('Is Team Leader?'),
                        ]),
                ]),

            // ✅ Dynamic form_submissions
            Section::make('Form Submission Details')
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

                    // Get all form fields from the application's form to display all fields, even if missing
                    $formFields = collect();
                    if ($record->form_id && $record->form) {
                        $formFields = $record->form->fields()
                            ->whereNotIn('type', ['section_header', 'paragraph']) // Skip display-only fields
                            ->orderBy('sort')
                            ->get();
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

                            // Handle boolean "Has a Team"
                            if ($key === 'has_team') {
                                $entries[] = TextEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->default($value !== null ? ($value ? 'Yes' : 'No') : '-')
                                    ->icon($value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->iconColor($value ? 'success' : 'danger');
                                continue;
                            }

                            // Files
                            if ($value !== null && is_string($value) && preg_match('/\.(jpg|jpeg|png|pdf|docx?|xlsx?|zip)$/i', $value)) {
                                $entries[] = ViewEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->view('filament.custom-entries.file-preview')
                                    ->viewData([
                                        'url' => Storage::url($value),
                                        'filename' => basename($value),
                                        'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                        'label' => $label,
                                    ]);
                                continue;
                            }

                            // Arrays - convert numeric values to labels for checkbox and multi_select
                            // Also handle string values that look like comma-separated numbers (e.g., "1,4")
                            if ($value !== null) {
                                // Check if it's an array or a string that should be treated as array
                                $isArrayValue = is_array($value);
                                $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                if ($isArrayValue || $isCommaSeparatedString) {
                                    // Convert string to array if needed
                                    $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                    // Pass the field object directly to avoid re-querying
                                    $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue, $field);
                                    $entries[] = TextEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->default($formattedValue ?? '-');
                                    continue;
                                }
                            }

                            // Handle radio button and dropdown values - convert numeric values to labels
                            $formattedValue = $value !== null
                                ? static::formatFormFieldValueStatic($key, $value, $field)
                                : '-';

                            $entries[] = TextEntry::make("form_submissions_{$key}")
                                ->label($label)
                                ->default($formattedValue ?? '-');
                        }

                        // Also add any fields in form_submissions that aren't in the form fields (like track, subtrack, etc.)
                        foreach ($data as $key => $value) {
                            if (!in_array($key, $addedFields)) {
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

                                // Handle boolean "Has a Team"
                                if ($key === 'has_team') {
                                    $entries[] = TextEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->default($value ? 'Yes' : 'No')
                                        ->icon($value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                        ->iconColor($value ? 'success' : 'danger');
                                    $addedFields[] = $key; // Mark as added to prevent duplicates
                                    continue;
                                }

                                // Files
                                if (is_string($value) && preg_match('/\.(jpg|jpeg|png|pdf|docx?|xlsx?|zip)$/i', $value)) {
                                    $entries[] = ViewEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->view('filament.custom-entries.file-preview')
                                        ->viewData([
                                            'url' => Storage::url($value),
                                            'filename' => basename($value),
                                            'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                            'label' => $label,
                                        ]);
                                    $addedFields[] = $key; // Mark as added to prevent duplicates
                                    continue;
                                }

                                // Arrays - convert numeric values to labels for checkbox and multi_select
                                // Also handle string values that look like comma-separated numbers (e.g., "1,4")
                                if ($value !== null) {
                                    // Check if it's an array or a string that should be treated as array
                                    $isArrayValue = is_array($value);
                                    $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                    if ($isArrayValue || $isCommaSeparatedString) {
                                        // Convert string to array if needed
                                        $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                        // Try to find the field from form fields first, then by form_id
                                        $formField = $formFields->firstWhere('slug', $key)
                                            ?? ($record->form_id ? \App\Models\FormField::where('form_id', $record->form_id)->where('slug', $key)->first() : null);
                                        $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue, $formField);
                                        $entries[] = TextEntry::make("form_submissions_{$key}")
                                            ->label($label)
                                            ->default($formattedValue ?? '-');
                                        $addedFields[] = $key; // Mark as added to prevent duplicates
                                        continue;
                                    }
                                }

                                // Handle radio, dropdown, checkbox - convert numeric values to labels
                                $formField = $formFields->firstWhere('slug', $key)
                                    ?? ($record->form_id ? \App\Models\FormField::where('form_id', $record->form_id)->where('slug', $key)->first() : null);
                                $formattedValue = static::formatFormFieldValueStatic($key, $value, $formField);

                                $entries[] = TextEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->default($formattedValue ?? '-');
                                $addedFields[] = $key; // Mark as added to prevent duplicates
                            }
                        }
                    } else {
                        // Fallback: if no form fields found, display what's in form_submissions (original behavior)
                        foreach ($data as $key => $value) {
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

                            // Handle boolean "Has a Team"
                            if ($key === 'has_team') {
                                $entries[] = TextEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->default($value ? 'Yes' : 'No')
                                    ->icon($value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->iconColor($value ? 'success' : 'danger');
                                continue;
                            }

                            // Files
                            if (is_string($value) && preg_match('/\.(jpg|jpeg|png|pdf|docx?|xlsx?|zip)$/i', $value)) {
                                $entries[] = ViewEntry::make("form_submissions_{$key}")
                                    ->label($label)
                                    ->view('filament.custom-entries.file-preview')
                                    ->viewData([
                                        'url' => Storage::url($value),
                                        'filename' => basename($value),
                                        'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                        'label' => $label,
                                    ]);
                                continue;
                            }

                            // Arrays - convert numeric values to labels for checkbox and multi_select
                            // Also handle string values that look like comma-separated numbers (e.g., "1,4")
                            if ($value !== null) {
                                // Check if it's an array or a string that should be treated as array
                                $isArrayValue = is_array($value);
                                $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

                                if ($isArrayValue || $isCommaSeparatedString) {
                                    // Convert string to array if needed
                                    $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
                                    $formField = $record->form_id ? \App\Models\FormField::where('form_id', $record->form_id)->where('slug', $key)->first() : null;
                                    $formattedValue = static::formatFormFieldValueStatic($key, $arrayValue, $formField);
                                    $entries[] = TextEntry::make("form_submissions_{$key}")
                                        ->label($label)
                                        ->default($formattedValue ?? '-');
                                    continue;
                                }
                            }

                            // Handle radio, dropdown, checkbox - convert numeric values to labels
                            $formField = $record->form_id ? \App\Models\FormField::where('form_id', $record->form_id)->where('slug', $key)->first() : null;
                            $formattedValue = static::formatFormFieldValueStatic($key, $value, $formField);

                            $entries[] = TextEntry::make("form_submissions_{$key}")
                                ->label($label)
                                ->default($formattedValue ?? '-');
                        }
                    }

                    // Add participant fields that are stored in participants table, not in form_submissions
                    // These should always be displayed, but only if not already added from form fields
                    $participantName = null;
                    $participantEmail = null;

                    if ($record->participant) {
                        $participantName = $record->participant->name ?? null;
                        $participantEmail = $record->participant->email ?? null;
                    } else {
                        // Fallback to form_submissions if participant not loaded
                        $participantName = $data['participant_name'] ?? null;
                        if (!$participantName) {
                            // Try to construct from first_name and last_name
                            $firstName = $data['first_name'] ?? '';
                            $lastName = $data['last_name'] ?? '';
                            if ($firstName || $lastName) {
                                $participantName = trim($firstName . ' ' . $lastName) ?: null;
                            }
                        }
                        $participantEmail = $data['participant_email'] ?? $data['email'] ?? null;
                    }

                    // Add participant_name if not already in form fields
                    if (!in_array('participant_name', $addedFields)) {
                        $entries[] = TextEntry::make('participant_name')
                            ->label('Participant Name')
                            ->default($participantName ?? '-');
                        $addedFields[] = 'participant_name'; // Mark as added to prevent duplicates
                    }

                    // Add participant_email if not already in form fields
                    if (!in_array('participant_email', $addedFields) && !in_array('email', $addedFields)) {
                        $entries[] = TextEntry::make('participant_email')
                            ->label('Participant Email')
                            ->default($participantEmail ?? '-');
                        $addedFields[] = 'participant_email'; // Mark as added to prevent duplicates
                        $addedFields[] = 'email'; // Also mark email to prevent duplicates
                    }

                    // Add team-related fields that are stored separately (not in form_submissions)
                    // These should always be displayed, even if empty, but only if not already added
                    $teamFields = [
                        'register_as' => [
                            'label' => 'Register As',
                            'value' => $record->registered_as ?? null,
                            'format' => 'text'
                        ],
                        'has_team' => [
                            'label' => 'Has Team',
                            'value' => $record->has_team ?? false,
                            'format' => 'boolean'
                        ],
                        'team_name' => [
                            'label' => 'Team Name',
                            'value' => $record->team_name ?? null,
                            'format' => 'text'
                        ],
                        'team_logo' => [
                            'label' => 'Team Logo',
                            'value' => $record->team_logo ?? null,
                            'format' => 'file'
                        ],
                        'team_serial' => [
                            'label' => 'Team Serial',
                            'value' => $record->team_serial ?? null,
                            'format' => 'array'
                        ],
                    ];

                    foreach ($teamFields as $key => $fieldInfo) {
                        // Check if this field was already added from form fields
                        if (!in_array($key, $addedFields)) {
                            $value = $fieldInfo['value'];

                            if ($fieldInfo['format'] === 'boolean') {
                                $entries[] = TextEntry::make("team_field_{$key}")
                                    ->label($fieldInfo['label'])
                                    ->default($value !== null ? ($value ? 'Yes' : 'No') : '-')
                                    ->icon($value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->iconColor($value ? 'success' : 'danger');
                            } elseif ($fieldInfo['format'] === 'file' && $value !== null && is_string($value)) {
                                $entries[] = ViewEntry::make("team_field_{$key}")
                                    ->label($fieldInfo['label'])
                                    ->view('filament.custom-entries.file-preview')
                                    ->viewData([
                                        'url' => Storage::url($value),
                                        'filename' => basename($value),
                                        'isImage' => preg_match('/\.(jpg|jpeg|png)$/i', $value),
                                        'label' => $fieldInfo['label'],
                                    ]);
                            } elseif ($fieldInfo['format'] === 'array' && is_array($value)) {
                                $entries[] = TextEntry::make("team_field_{$key}")
                                    ->label($fieldInfo['label'])
                                    ->default(collect($value)->join(', ') ?: '-');
                            } else {
                                $entries[] = TextEntry::make("team_field_{$key}")
                                    ->label($fieldInfo['label'])
                                    ->default($value ?? '-');
                            }
                            $addedFields[] = $key; // Mark as added to prevent duplicates
                        }
                    }

                    if (empty($entries)) {
                        $entries[] = TextEntry::make('no_data')->label('No Form Data Available')->default('-');
                    }

                    return $entries;
                })
                ->columns(2)

            // AI Evaluation
            , Section::make('AI Evaluation')
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
                ])

        ];
    }

    /**
     * Format form field value - convert numeric values to labels for option-based fields
     * Supports both single values and arrays (for checkbox and multi_select)
     *
     * @param string $fieldSlug The field slug
     * @param mixed $value The value to format (can be array or single value)
     * @param \App\Models\FormField|null $formField Optional: pass the form field object to avoid re-querying
     */
    protected static function formatFormFieldValueStatic($fieldSlug, $value, $formField = null)
    {
        // Handle string that is comma-separated IDs (e.g. "1,4,2,3" from checkbox/multi_select)
        // Must contain a comma to avoid infinite recursion when value is single "1"
        if (is_string($value) && str_contains($value, ',') && preg_match('/^\s*\d+(\s*,\s*\d+)*\s*$/', trim($value))) {
            $ids = array_map('trim', explode(',', $value));
            $labels = collect($ids)->map(fn ($v) => static::formatFormFieldValueStatic($fieldSlug, $v, $formField))->filter()->values();
            return $labels->isEmpty() ? $value : $labels->join(', ');
        }

        // Handle array values (for checkbox and multi_select)
        if (is_array($value)) {
            $labels = [];
            $currentLang = app()->getLocale();

            // Get the form field from database if not provided
            if (!$formField) {
                $formField = \App\Models\FormField::where('slug', $fieldSlug)->first();
            }

            if (!$formField || !$formField->options) {
                // If no field found, return formatted array
                return implode(', ', array_filter($value));
            }

            // Check if field has options (dropdown, multi_select, radio, rating, checkbox)
            if (!in_array($formField->type, ['dropdown', 'multi_select', 'radio', 'rating', 'checkbox'])) {
                // If not an option-based field, return formatted array
                return implode(', ', array_filter($value));
            }

            // Process options to handle both string and array formats; use array_values so indices are 0,1,2...
            $processedOptions = [];
            if (isset($formField->options['en']) && isset($formField->options['ar']) &&
                is_string($formField->options['en']) && is_string($formField->options['ar'])) {
                $enOptions = array_values(\App\Models\FormField::parseOptionsString($formField->options['en']));
                $arOptions = array_values(\App\Models\FormField::parseOptionsString($formField->options['ar']));
                $maxLength = max(count($enOptions), count($arOptions));
                for ($i = 0; $i < $maxLength; $i++) {
                    $processedOptions[] = [
                        'en' => $enOptions[$i] ?? '',
                        'ar' => $arOptions[$i] ?? ''
                    ];
                }
            } elseif (is_array($formField->options)) {
                if (isset($formField->options['ar']) && is_array($formField->options['ar'])) {
                    $enOptions = array_values($formField->options['en'] ?? []);
                    $arOptions = array_values($formField->options['ar'] ?? []);
                    $maxLength = max(count($enOptions), count($arOptions));
                    for ($i = 0; $i < $maxLength; $i++) {
                        $processedOptions[] = [
                            'en' => $enOptions[$i] ?? '',
                            'ar' => $arOptions[$i] ?? ''
                        ];
                    }
                } else {
                    $raw = $formField->options;
                    // Normalize numeric string keys (e.g. {"1": {...}, "2": {...}}) to 0-based
                    $keys = is_array($raw) ? array_keys($raw) : [];
                    $numericKeys = $keys !== [] && array_reduce($keys, fn ($c, $k) => $c && (is_numeric($k) || ctype_digit((string) $k)), true);
                    $processedOptions = ($numericKeys && ! isset($raw['en']) && ! isset($raw['ar'])) ? array_values($raw) : $raw;
                }
            }

            // Process each value in the array
            foreach ($value as $val) {
                // Skip empty values
                if ($val === null || $val === '') {
                    continue;
                }

                // Handle numeric values (id or index-based)
                if (is_numeric($val)) {
                    $intVal = (int) $val;
                    $label = null;
                    // Try by option id first
                    foreach ($processedOptions as $opt) {
                        if (is_array($opt) && isset($opt['id']) && (int) $opt['id'] === $intVal) {
                            $label = $currentLang === 'ar' ? ($opt['ar'] ?? $opt['en'] ?? '') : ($opt['en'] ?? $opt['ar'] ?? '');
                            break;
                        }
                    }
                    // Try 1-based index
                    if ($label === null) {
                        $index = $intVal - 1;
                        if ($index >= 0 && isset($processedOptions[$index])) {
                            $option = $processedOptions[$index];
                            if (is_array($option)) {
                                $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                            } elseif (is_string($option)) {
                                $label = $option;
                            }
                        }
                    }
                    // Try 0-based index
                    if ($label === null && isset($processedOptions[$intVal])) {
                        $option = $processedOptions[$intVal];
                        if (is_array($option)) {
                            $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                        } elseif (is_string($option)) {
                            $label = $option;
                        }
                    }
                    if ($label) {
                        $labels[] = $label;
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
                                $label = $currentLang === 'ar'
                                    ? ($optionLabelAr ?? $optionLabelEn ?? '')
                                    : ($optionLabelEn ?? $optionLabelAr ?? '');
                                if ($label) {
                                    $labels[] = $label;
                                }
                                $found = true;
                                break;
                            }
                        } elseif (is_string($option) && $option == $val) {
                            $labels[] = $option;
                            $found = true;
                            break;
                        }
                    }

                    // If no matching option found, keep the original value
                    if (!$found && $val !== '') {
                        $labels[] = $val;
                    }
                }
            }

            return implode(', ', $labels);
        }

        // Handle single value
        // If value is not numeric, return as is
        if (!is_numeric($value)) {
            return $value;
        }

        // Get the form field from database if not provided
        if (!$formField) {
            $formField = \App\Models\FormField::where('slug', $fieldSlug)->first();
        }

        if (!$formField || !$formField->options) {
            return $value;
        }

        // Check if field has options (dropdown, multi_select, radio, rating, checkbox)
        if (!in_array($formField->type, ['dropdown', 'multi_select', 'radio', 'rating', 'checkbox'])) {
            return $value;
        }

        // Process options to handle both string and array formats
        $processedOptions = [];
        $isStringFormat = false;
            if (isset($formField->options['en']) && isset($formField->options['ar']) &&
                is_string($formField->options['en']) && is_string($formField->options['ar'])) {
                $isStringFormat = true;
                // Convert string format to array; use array_values so indices are 0,1,2... (value 1 = first option)
                $enOptions = array_values(\App\Models\FormField::parseOptionsString($formField->options['en']));
                $arOptions = array_values(\App\Models\FormField::parseOptionsString($formField->options['ar']));
                $maxLength = max(count($enOptions), count($arOptions));
                for ($i = 0; $i < $maxLength; $i++) {
                    $processedOptions[] = [
                        'en' => $enOptions[$i] ?? '',
                        'ar' => $arOptions[$i] ?? ''
                    ];
                }
            } elseif (is_array($formField->options)) {
                if (isset($formField->options['ar']) && is_array($formField->options['ar'])) {
                    $enOptions = array_values($formField->options['en'] ?? []);
                    $arOptions = array_values($formField->options['ar'] ?? []);
                    $maxLength = max(count($enOptions), count($arOptions));
                    for ($i = 0; $i < $maxLength; $i++) {
                        $processedOptions[] = [
                            'en' => $enOptions[$i] ?? '',
                            'ar' => $arOptions[$i] ?? ''
                        ];
                    }
                } else {
                    $raw = $formField->options;
                    $keys = is_array($raw) ? array_keys($raw) : [];
                    $numericKeys = $keys !== [] && array_reduce($keys, fn ($c, $k) => $c && (is_numeric($k) || ctype_digit((string) $k)), true);
                    $processedOptions = ($numericKeys && ! isset($raw['en']) && ! isset($raw['ar'])) ? array_values($raw) : $raw;
                }
            }

        // For string format options, try to find the value directly in the options first
        if ($isStringFormat && !empty($processedOptions)) {
            $valueStr = (string)$value;
            foreach ($processedOptions as $index => $option) {
                if (is_array($option)) {
                    $enVal = (string)($option['en'] ?? '');
                    $arVal = (string)($option['ar'] ?? '');
                    // Check if value matches the option value directly
                    if ($enVal === $valueStr || $arVal === $valueStr) {
                        // Return the appropriate language value
                        $currentLang = app()->getLocale();
                        return $currentLang === 'ar' ? $arVal : $enVal;
                    }
                }
            }
        }

        $currentLang = app()->getLocale();
        $intValue = (int) $value;

        // Helper to get display label from an option (en/ar or label/value)
        $getLabelFromOption = function ($option) use ($currentLang) {
            if (! is_array($option)) {
                return is_string($option) ? $option : null;
            }
            $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
            if ($label !== '') {
                return $label;
            }
            // Option has no en/ar — use label or value (e.g. from API formatOptions)
            if (is_string($option['label'] ?? null)) {
                return $option['label'];
            }
            if (isset($option['value'])) {
                return is_string($option['value']) ? $option['value'] : (string) $option['value'];
            }
            return null;
        };

        // Try by option id first
        foreach ($processedOptions as $option) {
            if (is_array($option) && isset($option['id']) && (int) $option['id'] === $intValue) {
                $label = $getLabelFromOption($option);
                return $label !== null && $label !== '' ? $label : $value;
            }
        }

        // Try 1-based index (value 1 = first option)
        $indexOneBased = $intValue - 1;
        if ($indexOneBased >= 0 && isset($processedOptions[$indexOneBased])) {
            $option = $processedOptions[$indexOneBased];
            $label = $getLabelFromOption($option);
            if ($label !== null && $label !== '') {
                return $label;
            }
            return $value;
        }

        // Try 0-based index
        if (isset($processedOptions[$intValue])) {
            $option = $processedOptions[$intValue];
            $label = $getLabelFromOption($option);
            if ($label !== null && $label !== '') {
                return $label;
            }
            return $value;
        }

        // Try by numeric string key (e.g. options stored as {"1": {...}, "2": {...}})
        if (isset($processedOptions[(string) $intValue])) {
            $option = $processedOptions[(string) $intValue];
            $label = $getLabelFromOption($option);
            if ($label !== null && $label !== '') {
                return $label;
            }
        }

        // Fallback: use FormField's resolveValueToLabel (uses processed_options)
        $resolved = $formField->resolveValueToLabel($value, $currentLang);
        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        return $value;
    }
}
