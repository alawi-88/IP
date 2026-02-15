<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static create(array $array)
 */
class ProjectEvaluation extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'judge_project_id',
        'form_id',
        'stage_id',
        'question',
        'details',
        'answer',
        'comment',
        'weight',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'details' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'judge_project_id',
        'form_id',
        'question',
        'details',
        'answer',
        'comment',
        'weight',
        'is_archived',
        'archived_at',
    ];

    protected string $moduleName = 'Project Evaluation';
    protected string $logName = 'project_evaluation';

    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function ($evaluation) {
                FormEvaluationScore::where('judge_project_id', $evaluation->judge_project_id)
                    ->where('form_id', $evaluation->form_id)
                    ->where('stage_id', $evaluation->stage_id)
                    ->delete();

            $evaluationsAnswers = ProjectEvaluation::where('judge_project_id', $evaluation->judge_project_id)->get();

            $newEvaluationScore = $evaluationsAnswers->average('answer') ?: 0;

            JudgeProject::where('id', $evaluation->judge_project_id)
                ->update(['evaluation_score' => $newEvaluationScore]);
        });
    }

    public function getAnswerAttribute($value)
    {
        return number_format($value, 2);
    }

    public static function getQuestionWeights($question): int
    {
        return static::$questionWeights[$question];
    }

    public function judgeProject(): BelongsTo
    {
        return $this->belongsTo(JudgeProject::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectEvaluationNote::class);
    }

    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(function ($record) {
                    return static::getEvaluations($record->pivot->project_id, $record->pivot->judge_id)->flatMap(function ($evaluation) {
                        return [
                            TextEntry::make('question')->getStateUsing($evaluation->question),
                            TextEntry::make('answer')->getStateUsing($evaluation->answer),
                        ];
                    });
                }),
        ];
    }

    private static function getEvaluations($projectId, $judgeId)
    {
        $assignment = JudgeProject::where('project_id', $projectId)
            ->where('judge_id', $judgeId)->first();

        return ProjectEvaluation::where('judge_project_id', $assignment->id)->get();
    }

    public static function columns(): array
    {
        return [
            TextColumn::make('form_name')
                ->label('Form'),

            TextColumn::make('judge_name')
                ->label('Judge'),

            TextColumn::make('evaluation_score')->label('Score')
                ->badge()
                ->color('primary')
                ->formatStateUsing(fn ($state) => number_format($state, 2) . ' %'),

            TextColumn::make('created_at')->since()->label('Evaluated'),

        ];
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    /**
     * Check if the evaluation is archived
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * Archive the evaluation
     */
    public function archive(): bool
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        // Check if project has any active evaluations after archiving this one
        if ($result && $this->judgeProject && $this->judgeProject->project) {
            $project = $this->judgeProject->project;
            
            // Check if there are any active evaluations for this project
            $hasActiveEvaluations = ProjectEvaluation::whereHas('judgeProject', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })->where('is_archived', false)->exists();
            
            // If no active evaluations, set evaluation status to false
            if (!$hasActiveEvaluations) {
                $project->setEvaluationStatusAs(false);
            }
            
            // Recalculate project score
            $project->updateScore();
        }

        return $result;
    }

    /**
     * Restore the evaluation from archive
     */
    public function restore(): bool
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        // Update project evaluation status and recalculate score
        if ($result && $this->judgeProject && $this->judgeProject->project) {
            $project = $this->judgeProject->project;
            
            // Set evaluation status to true
            $project->setEvaluationStatusAs(true);
            
            // Recalculate project score
            $project->updateScore();
        }

        return $result;
    }

    /**
     * Scope to get only active (non-archived) evaluations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get only archived evaluations
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }
}
