<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained('competitions', 'id', 'par_program_id_foreign')->onDelete('cascade');
            $table->enum('action_type', ['create', 'update', 'delete', 'archive']);
            $table->foreignId('requested_by')->constrained('users', 'id', 'par_requested_by_foreign')->onDelete('cascade');
            $table->foreignId('approval_workflow_id')->constrained('approval_workflows', 'id', 'par_workflow_id_foreign')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('action_data')->nullable(); // Store the data for the action
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'par_status_created_idx');
            $table->index(['requested_by', 'status'], 'par_requested_status_idx');
            $table->index(['action_type', 'status'], 'par_action_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_approval_requests');
    }
};
