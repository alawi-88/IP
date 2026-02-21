<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Stage;
use App\Models\FormEvaluationScore;
use Illuminate\Console\Command;

class AnalyzeProjectScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:analyze-scores {--project-id= : Analyze specific project by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze project scores and evaluation stages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->option('project-id');
        
        if ($projectId) {
            $this->analyzeProject($projectId);
            return 0;
        }
        
        $this->info('Analyzing all projects...');
        
        $projects = Project::whereNotNull('total_score')->get();
        
        $this->table(
            ['Project ID', 'Application ID', 'Current Score', 'Last Stage', 'Evaluations Count', 'Issues'],
            $projects->map(function ($project) {
                $issues = [];
                
                if (!$project->application_id) {
                    $issues[] = 'No application_id';
                }
                
                $lastStage = $project->getLastStage();
                if (!$lastStage) {
                    $issues[] = 'No last stage';
                }
                
                $evaluationsCount = 0;
                if ($lastStage) {
                    $evaluationsCount = FormEvaluationScore::whereHas('judgeProject', function ($query) use ($project) {
                            $query->where('project_id', $project->id);
                        })
                        ->where('stage_id', $lastStage->id)
                        ->where('exclude_from_calculation', false)
                        ->where('is_archived', false)
                        ->where('evaluation_score', '>', 0)
                        ->count();
                        
                    if ($evaluationsCount === 0) {
                        $issues[] = 'No evaluations in last stage';
                    }
                }
                
                return [
                    $project->id,
                    $project->application_id ?: 'N/A',
                    $project->total_score . '%',
                    $lastStage ? $lastStage->id : 'N/A',
                    $evaluationsCount,
                    implode(', ', $issues) ?: 'OK'
                ];
            })
        );
        
        return 0;
    }
    
    private function analyzeProject($projectId)
    {
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->error("Project with ID {$projectId} not found.");
            return;
        }
        
        $this->info("Analyzing Project {$project->id}:");
        $this->line("Current Score: {$project->total_score}%");
        $this->line("Application ID: " . ($project->application_id ?: 'N/A'));
        
        if (!$project->application_id) {
            $this->error("❌ No application_id - cannot determine program");
            return;
        }
        
        $program = $project->application?->program;
        if (!$program) {
            $this->error("❌ No program found for application");
            return;
        }
        
        $this->line("Program: {$program->title}");
        
        // Get all stages for this program
        $stages = Stage::where('program_id', $program->id)
            ->orderBy('ends_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $this->line("\nStages in program:");
        foreach ($stages as $stage) {
            $this->line("- Stage {$stage->id}: {$stage->title} (ends: {$stage->ends_at})");
        }
        
        $lastStage = $project->getLastStage();
        if (!$lastStage) {
            $this->error("❌ No last stage found");
            return;
        }
        
        $this->line("\nLast Stage: {$lastStage->id} - {$lastStage->title}");
        
        // Get evaluations for this project in the last stage
        $evaluations = FormEvaluationScore::whereHas('judgeProject', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })
            ->where('stage_id', $lastStage->id)
            ->where('exclude_from_calculation', false)
            ->where('is_archived', false)
            ->where('evaluation_score', '>', 0)
            ->get();
            
        $this->line("\nEvaluations in last stage: {$evaluations->count()}");
        
        if ($evaluations->count() > 0) {
            $this->line("Evaluation scores:");
            foreach ($evaluations as $eval) {
                $this->line("- Judge {$eval->judgeProject->judge_id}: {$eval->evaluation_score}%");
            }
            
            $avgScore = $evaluations->avg('evaluation_score');
            $this->line("\nAverage score: " . number_format($avgScore, 2) . "%");
        } else {
            $this->warn("⚠️ No evaluations found in last stage");
        }
        
        // Check evaluations in other stages
        $allEvaluations = FormEvaluationScore::whereHas('judgeProject', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })
            ->where('exclude_from_calculation', false)
            ->where('is_archived', false)
            ->where('evaluation_score', '>', 0)
            ->get()
            ->groupBy('stage_id');
            
        $this->line("\nEvaluations by stage:");
        foreach ($allEvaluations as $stageId => $stageEvaluations) {
            $avgScore = $stageEvaluations->avg('evaluation_score');
            $this->line("- Stage {$stageId}: {$stageEvaluations->count()} evaluations, avg: " . number_format($avgScore, 2) . "%");
        }
    }
}
