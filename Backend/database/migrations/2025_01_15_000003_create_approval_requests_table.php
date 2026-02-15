<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // The action being requested for approval
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Admin who requested
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade'); // Reference to workflow
            $table->json('action_data'); // Data related to the action being approved
            $table->text('reason')->nullable(); // Reason for the action
            $table->text('rejection_reason')->nullable(); // Reason for rejection
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index(['action', 'status']);
            $table->index(['requested_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
