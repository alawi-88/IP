<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\JudgeProject;
use App\Models\ProjectEvaluation;
use App\Models\FormEvaluationScore;
use Illuminate\Console\Command;

class TestReevaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:reevaluation {--project-id= : Test specific project by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test re-evaluation functionality with archived evaluations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->option('project-id');
        
        if ($projectId) {
            $this->testProjectReevaluation($projectId);
            return 0;
        }
        
        $this->info('Testing re-evaluation functionality...');
        
        // Find projects with archived evaluations through JudgeProject
        $projects = Project::whereHas('judges', function ($query) {
            $query->whereHas('evaluations', function ($q) {
                $q->where('is_archived', true);
            });
        })->get();
        
        $this->info("Found {$projects->count()} projects with archived evaluations");
        
        foreach ($projects as $project) {
            $this->line("Project {$project->id}:");
            
            // Get judge projects for this project
            $judgeProjects = JudgeProject::where('project_id', $project->id)->get();
            
            foreach ($judgeProjects as $judgeProject) {
                $archivedEvaluations = $judgeProject->evaluations()->where('is_archived', true)->count();
                $activeEvaluations = $judgeProject->evaluations()->where('is_archived', false)->count();
                
                $this->line("  Judge {$judgeProject->judge_id}: {$archivedEvaluations} archived, {$activeEvaluations} active evaluations");
            }
        }
        
        return 0;
    }
    
    private function testProjectReevaluation($projectId)
    {
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->error("Project with ID {$projectId} not found.");
            return;
        }
        
        $this->info("Testing re-evaluation for Project {$project->id}:");
        
        // Get judge projects for this project
        $judgeProjects = JudgeProject::where('project_id', $project->id)->get();
        
        if ($judgeProjects->isEmpty()) {
            $this->warn("No judge assignments found for this project.");
            return;
        }
        
        foreach ($judgeProjects as $judgeProject) {
            $this->line("\nJudge {$judgeProject->judge_id}:");
            
            // Check archived evaluations
            $archivedEvaluations = $judgeProject->evaluations()->where('is_archived', true)->get();
            $this->line("  Archived evaluations: {$archivedEvaluations->count()}");
            
            foreach ($archivedEvaluations as $eval) {
                $this->line("    - Question: {$eval->question}, Answer: {$eval->answer}, Archived: {$eval->archived_at}");
            }
            
            // Check active evaluations
            $activeEvaluations = $judgeProject->evaluations()->where('is_archived', false)->get();
            $this->line("  Active evaluations: {$activeEvaluations->count()}");
            
            foreach ($activeEvaluations as $eval) {
                $this->line("    - Question: {$eval->question}, Answer: {$eval->answer}");
            }
            
            // Check FormEvaluationScores
            $archivedScores = FormEvaluationScore::where('judge_project_id', $judgeProject->id)
                ->where('is_archived', true)
                ->get();
            $this->line("  Archived scores: {$archivedScores->count()}");
            
            $activeScores = FormEvaluationScore::where('judge_project_id', $judgeProject->id)
                ->where('is_archived', false)
                ->get();
            $this->line("  Active scores: {$activeScores->count()}");
            
            foreach ($activeScores as $score) {
                $this->line("    - Form: {$score->form_id}, Stage: {$score->stage_id}, Score: {$score->evaluation_score}%");
            }
        }
    }
}
