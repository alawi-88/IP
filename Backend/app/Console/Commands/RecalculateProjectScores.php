<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class RecalculateProjectScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:recalculate-scores {--project-id= : Recalculate specific project by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate project scores using only the last stage evaluations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->option('project-id');
        
        if ($projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                $this->error("Project with ID {$projectId} not found.");
                return 1;
            }
            
            $this->info("Recalculating score for project: {$project->id}");
            $oldScore = $project->total_score;
            $project->updateScore();
            $newScore = $project->fresh()->total_score;
            
            $this->info("Project {$project->id} - Old score: {$oldScore}%, New score: {$newScore}%");
            return 0;
        }
        
        $this->info('Starting project score recalculation...');
        
        $projects = Project::whereNotNull('total_score')->get();
        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        foreach ($projects as $project) {
            try {
                $oldScore = $project->total_score;
                
                // Skip projects without application_id
                if (!$project->application_id) {
                    $this->warn("\nSkipping project {$project->id}: No application_id");
                    $bar->advance();
                    continue;
                }
                
                $project->updateScore();
                $newScore = $project->fresh()->total_score;
                
                if ($oldScore != $newScore) {
                    $updated++;
                    $this->line("\nProject {$project->id}: {$oldScore}% → {$newScore}%");
                } else {
                    $this->info("\nProject {$project->id}: No change ({$oldScore}%)");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError updating project {$project->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Recalculation completed!");
        $this->info("Projects updated: {$updated}");
        $this->info("Errors: {$errors}");
        
        return 0;
    }
}
