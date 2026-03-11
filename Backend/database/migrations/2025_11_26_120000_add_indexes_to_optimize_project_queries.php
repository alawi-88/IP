<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'is_archived') && Schema::hasColumn('projects', 'form_id')) {
            $__indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND INDEX_NAME = 'idx_projects_form_archived_created'");
            if (empty($__indexes)) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->index(['form_id', 'is_archived', 'created_at'], 'idx_projects_form_archived_created');
                });
            }
            $__indexes2 = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND INDEX_NAME = 'idx_projects_application_archived'");
            if (empty($__indexes2)) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->index(['application_id', 'is_archived'], 'idx_projects_application_archived');
                });
            }
        }

        if (Schema::hasTable('competition_applications')) {
            $__indexes3 = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'competition_applications' AND INDEX_NAME = 'idx_applications_id_participant'");
            if (empty($__indexes3)) {
                Schema::table('competition_applications', function (Blueprint $table) {
                    $table->index(['id', 'participant_id'], 'idx_applications_id_participant');
                });
            }
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


