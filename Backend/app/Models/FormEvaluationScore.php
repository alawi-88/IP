<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class FormEvaluationScore extends Model
{
    use LogsActivity, HasActivityLog;

    protected $table = 'form_evaluation_scores';
    
    protected $fillable = [
        'judge_project_id',
        'form_id',
        'stage_id',
        'evaluation_score',
        'is_archived',
        'archived_at',
        'exclude_from_calculation',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'exclude_from_calculation' => 'boolean',
    ];

    protected array $logFields = [
        'judge_project_id',
        'form_id',
        'stage_id',
        'evaluation_score',
        'is_archived',
        'archived_at',
        'exclude_from_calculation',
    ];

    protected string $moduleName = 'Form Evaluation Score';
    protected string $logName = 'form_evaluation_score';

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly($this->logFields)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->logName);
    }



    public function judgeProject()
    {
        return $this->belongsTo(JudgeProject::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
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
        // Archive the FormEvaluationScore
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        // Also archive all related ProjectEvaluation records
        $projectEvaluations = ProjectEvaluation::where('judge_project_id', $this->judge_project_id)
            ->where('form_id', $this->form_id)
            ->get();

        foreach ($projectEvaluations as $evaluation) {
            $evaluation->archive();
        }

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
        // Restore the FormEvaluationScore
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        // Also restore all related ProjectEvaluation records
        $projectEvaluations = ProjectEvaluation::where('judge_project_id', $this->judge_project_id)
            ->where('form_id', $this->form_id)
            ->get();

        foreach ($projectEvaluations as $evaluation) {
            $evaluation->restore();
        }

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
     * Exclude this evaluation from average calculations
     */
    public function excludeFromCalculation(): bool
    {
        try {
            $this->update([
                'exclude_from_calculation' => true,
            ]);

            // Recalculate project score after exclusion
            if ($this->judgeProject && $this->judgeProject->project) {
                $this->judgeProject->project->updateScore();
            }

            return true;
        } catch (\Exception $e) {
            // Failed to exclude evaluation from calculation
            throw $e;
        }
    }

    /**
     * Include this evaluation in average calculations
     */
    public function includeInCalculation(): bool
    {
        try {
            $this->update([
                'exclude_from_calculation' => false,
            ]);

            // Recalculate project score after inclusion
            if ($this->judgeProject && $this->judgeProject->project) {
                $this->judgeProject->project->updateScore();
            }

            return true;
        } catch (\Exception $e) {
            // Failed to include evaluation in calculation
            throw $e;
        }
    }

    /**
     * Check if this evaluation is excluded from calculations
     */
    public function isExcludedFromCalculation(): bool
    {
        return (bool) $this->exclude_from_calculation;
    }

    /**
     * Scope to get only active (non-archived) evaluations
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get only archived evaluations
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to get evaluations included in calculations
     */
    public function scopeIncludedInCalculation($query)
    {
        return $query->where('exclude_from_calculation', false);
    }

    /**
     * Scope to get evaluations excluded from calculations
     */
    public function scopeExcludedFromCalculation($query)
    {
        return $query->where('exclude_from_calculation', true);
    }
}
