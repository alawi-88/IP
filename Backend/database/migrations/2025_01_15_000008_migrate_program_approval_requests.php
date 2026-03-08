<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Check if program_approval_requests table exists
        if (Schema::hasTable('program_approval_requests')) {
            // Migrate data from program_approval_requests to approval_requests
            $programRequests = DB::table('program_approval_requests')->get();
            
            foreach ($programRequests as $programRequest) {
                // Insert into approval_requests
                DB::table('approval_requests')->insert([
                    'action' => "program.{$programRequest->action_type}",
                    'target_type' => 'App\\Models\\Competition',
                    'target_id' => $programRequest->program_id,
                    'status' => $programRequest->status,
                    'requested_by' => $programRequest->requested_by,
                    'approval_workflow_id' => $programRequest->approval_workflow_id,
                    'reason' => $programRequest->reason,
                    'rejection_reason' => $programRequest->rejection_reason,
                    'action_data' => json_encode(array_merge(
                        json_decode($programRequest->action_data ?? '{}', true),
                        ['action_type' => $programRequest->action_type]
                    )),
                    'approved_at' => $programRequest->approved_at,
                    'rejected_at' => $programRequest->rejected_at,
                    'executed_at' => $programRequest->executed_at,
                    'created_at' => $programRequest->created_at,
                    'updated_at' => $programRequest->updated_at,
                ]);
            }
            
            // Migrate approval levels
            if (Schema::hasTable('program_approval_request_levels')) {
                $programLevels = DB::table('program_approval_request_levels')->get();
                
                foreach ($programLevels as $programLevel) {
                    // Find the corresponding approval_request_id
                    $approvalRequest = DB::table('approval_requests')
                        ->where('target_type', 'App\\Models\\Competition')
                        ->where('target_id', $programLevel->program_approval_request_id)
                        ->first();
                    
                    if ($approvalRequest) {
                        DB::table('approval_request_levels')->insert([
                            'approval_request_id' => $approvalRequest->id,
                            'level_number' => $programLevel->level_number,
                            'status' => $programLevel->status,
                            'approver_id' => $programLevel->approver_id,
                            'approver_comment' => $programLevel->rejection_reason,
                            'approved_at' => $programLevel->approved_at,
                            'rejected_at' => $programLevel->rejected_at,
                            'created_at' => $programLevel->created_at,
                            'updated_at' => $programLevel->updated_at,
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        // This migration only migrates data, so down() is empty
        // The original tables will be dropped by other migrations
    }
};
