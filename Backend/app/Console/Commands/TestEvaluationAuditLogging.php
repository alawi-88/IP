<?php

namespace App\Console\Commands;

use App\Models\FormEvaluationScore;
use App\Models\ProjectEvaluation;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class TestEvaluationAuditLogging extends Command
{
    protected $signature = 'test:evaluation-audit-logging {--evaluation-id= : The ID of a specific evaluation to test}';
    protected $description = 'Test audit logging for evaluation archiving and restoration';

    public function handle()
    {
        $evaluationId = $this->option('evaluation-id');
        
        if ($evaluationId) {
            $this->testSpecificEvaluation($evaluationId);
            return 0;
        }
        
        $this->info('Testing evaluation audit logging...');
        
        // Find active evaluations
        $evaluations = FormEvaluationScore::where('is_archived', false)->limit(5)->get();
        
        if ($evaluations->isEmpty()) {
            $this->warn('No active evaluations found to test.');
            return 0;
        }
        
        $this->info("Found {$evaluations->count()} active evaluations to test");
        
        foreach ($evaluations as $evaluation) {
            $this->testEvaluationAuditLogging($evaluation);
        }
        
        return 0;
    }
    
    private function testSpecificEvaluation($evaluationId)
    {
        $evaluation = FormEvaluationScore::find($evaluationId);
        
        if (!$evaluation) {
            $this->error("Evaluation with ID {$evaluationId} not found.");
            return;
        }
        
        $this->info("Testing audit logging for Evaluation {$evaluation->id}:");
        $this->testEvaluationAuditLogging($evaluation);
    }
    
    private function testEvaluationAuditLogging($evaluation)
    {
        $this->line("\n--- Testing Evaluation {$evaluation->id} ---");
        $this->line("Current status: " . ($evaluation->is_archived ? 'Archived' : 'Active'));
        $this->line("Score: {$evaluation->evaluation_score}%");
        $this->line("Form ID: {$evaluation->form_id}");
        $this->line("Stage ID: {$evaluation->stage_id}");
        
        // Get audit logs before archiving
        $logsBefore = Activity::where('subject_type', FormEvaluationScore::class)
            ->where('subject_id', $evaluation->id)
            ->count();
        
        $this->line("Audit logs before: {$logsBefore}");
        
        // Show recent logs
        $recentLogs = Activity::where('subject_type', FormEvaluationScore::class)
            ->where('subject_id', $evaluation->id)
            ->latest()
            ->limit(3)
            ->get();
        
        $this->line("Recent logs:");
        foreach ($recentLogs as $log) {
            $this->line("  - {$log->description} (Event: {$log->event}) at {$log->created_at}");
        }
        
        if (!$evaluation->is_archived) {
            // Test archiving
            $this->info("Testing archive...");
            $result = $evaluation->archive();
            
            if ($result) {
                $this->info("✅ Archive successful");
                
                // Check audit logs after archiving
                $logsAfter = Activity::where('subject_type', FormEvaluationScore::class)
                    ->where('subject_id', $evaluation->id)
                    ->count();
                
                $this->line("Audit logs after: {$logsAfter}");
                
                if ($logsAfter > $logsBefore) {
                    $this->info("✅ Audit log created for archive");
                    
                    // Show the latest audit log
                    $latestLog = Activity::where('subject_type', FormEvaluationScore::class)
                        ->where('subject_id', $evaluation->id)
                        ->latest()
                        ->first();
                    
                    if ($latestLog) {
                        $this->line("Latest log: {$latestLog->description} at {$latestLog->created_at}");
                        $this->line("Properties: " . json_encode($latestLog->properties));
                    }
                } else {
                    $this->error("❌ No audit log created for archive");
                }
                
                // Test restoration
                $this->info("Testing restore...");
                $result = $evaluation->restore();
                
                if ($result) {
                    $this->info("✅ Restore successful");
                    
                    // Check audit logs after restoration
                    $logsAfterRestore = Activity::where('subject_type', FormEvaluationScore::class)
                        ->where('subject_id', $evaluation->id)
                        ->count();
                    
                    $this->line("Audit logs after restore: {$logsAfterRestore}");
                    
                    if ($logsAfterRestore > $logsAfter) {
                        $this->info("✅ Audit log created for restore");
                    } else {
                        $this->error("❌ No audit log created for restore");
                    }
                } else {
                    $this->error("❌ Restore failed");
                }
            } else {
                $this->error("❌ Archive failed");
            }
        } else {
            $this->info("Evaluation is already archived, testing restore...");
            
            $result = $evaluation->restore();
            
            if ($result) {
                $this->info("✅ Restore successful");
                
                // Check audit logs after restoration
                $logsAfter = Activity::where('subject_type', FormEvaluationScore::class)
                    ->where('subject_id', $evaluation->id)
                    ->count();
                
                $this->line("Audit logs after restore: {$logsAfter}");
                
                if ($logsAfter > $logsBefore) {
                    $this->info("✅ Audit log created for restore");
                } else {
                    $this->error("❌ No audit log created for restore");
                }
            } else {
                $this->error("❌ Restore failed");
            }
        }
        
        // Check ProjectEvaluation audit logs
        $projectEvaluationLogs = Activity::where('subject_type', ProjectEvaluation::class)
            ->whereHas('subject', function ($query) use ($evaluation) {
                $query->where('judge_project_id', $evaluation->judge_project_id)
                      ->where('form_id', $evaluation->form_id);
            })
            ->count();
        
        $this->line("Related ProjectEvaluation audit logs: {$projectEvaluationLogs}");
    }
}
