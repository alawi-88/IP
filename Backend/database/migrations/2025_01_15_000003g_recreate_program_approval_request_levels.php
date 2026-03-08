<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop the table if it exists (in case it was partially created)
        Schema::dropIfExists('program_approval_request_levels');
        
        // Recreate the table with proper index names
        Schema::create('program_approval_request_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_approval_request_id')->constrained('program_approval_requests', 'id', 'par_levels_request_id_foreign')->onDelete('cascade');
            $table->integer('level_number');
            $table->foreignId('approver_id')->constrained('users', 'id', 'par_levels_approver_id_foreign')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['program_approval_request_id', 'level_number'], 'par_levels_unique');
            $table->index(['program_approval_request_id', 'status'], 'par_levels_request_status_idx');
            $table->index(['approver_id', 'status'], 'par_levels_approver_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_approval_request_levels');
    }
};
