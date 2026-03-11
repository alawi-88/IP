<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds indexes to optimize the query:
     * SELECT * FROM projects 
     * WHERE form_id = ? 
     * AND EXISTS (SELECT * FROM competition_applications WHERE projects.application_id = competition_applications.id AND participant_id = ?)
     * AND is_archived = 0 
     * ORDER BY created_at DESC 
     * LIMIT 1
     */
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            // Composite index for filtering by form_id, is_archived, and sorting by created_at
            // This allows MySQL to efficiently filter and sort without loading all rows into memory
            $table->index(['form_id', 'is_archived', 'created_at'], 'idx_projects_form_archived_created');
            
            // Index for the application_id join condition
            // This helps optimize the EXISTS subquery
            $table->index(['application_id', 'is_archived'], 'idx_projects_application_archived');
        });
        }

        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            // Composite index for the EXISTS subquery
            // This allows MySQL to quickly find matching records by id and participant_id
            $table->index(['id', 'participant_id'], 'idx_applications_id_participant');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_form_archived_created');
            $table->dropIndex('idx_projects_application_archived');
        });
        }

        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_id_participant');
        });
        }
    }
};


