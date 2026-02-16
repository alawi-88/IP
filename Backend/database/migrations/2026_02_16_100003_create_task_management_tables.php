<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Epic IN-2031 & IN-2028: Task Management (Admin + Participant)
     * Creates task templates, assignments, submissions, and history tables
     */
    public function up(): void
    {
        // Task templates - reusable task definitions linked to forms
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title'); // bilingual EN/AR
            $table->json('description')->nullable(); // bilingual EN/AR
            $table->json('instructions')->nullable(); // bilingual EN/AR
            $table->string('difficulty_level')->nullable(); // easy, medium, hard
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['competition_id', 'is_archived']);
        });

        // Task assignments - assigned tasks to teams/participants
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();

            // Assignee - can be team, participant, or all
            $table->enum('assignment_type', ['team', 'participant', 'all'])->default('team');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();

            $table->json('title'); // bilingual EN/AR (can override template)
            $table->json('description')->nullable();
            $table->json('instructions')->nullable();
            $table->date('due_date');
            $table->enum('status', [
                'not_started', 'in_progress', 'submitted',
                'revision_requested', 'approved', 'rejected'
            ])->default('not_started');
            $table->json('allowed_file_formats')->nullable(); // e.g. ["pdf","docx","xlsx"]
            $table->unsignedInteger('max_file_size_mb')->default(25);
            $table->json('assignment_notes')->nullable(); // bilingual
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['competition_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index(['participant_id', 'status']);
            $table->index('due_date');
        });

        // Task submissions - deliverables submitted by participants
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('participants')->cascadeOnDelete();
            $table->json('form_submissions')->nullable(); // schemaless attributes for form data
            $table->json('files')->nullable(); // array of file paths
            $table->text('notes')->nullable(); // participant notes
            $table->unsignedInteger('version')->default(1); // revision version
            $table->enum('status', ['submitted', 'approved', 'rejected', 'revision_requested'])->default('submitted');
            $table->text('admin_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['task_assignment_id', 'version']);
        });

        // Task comments - communication between admin and participant
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable'); // user or participant
            $table->text('body');
            $table->boolean('is_internal')->default(false); // admin-only comments
            $table->timestamps();

            $table->index('task_assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_submissions');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('task_templates');
    }
};
