<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('teams')) {
            // Drop foreign keys using raw SQL with IF EXISTS pattern
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $foreignKeys);

            if (in_array('teams_idea_path_id_foreign', $fkNames)) {
                Schema::table('teams', function (Blueprint $table) {
                    $table->dropForeign(['idea_path_id']);
                });
            }
            if (in_array('teams_idea_challenge_id_foreign', $fkNames)) {
                Schema::table('teams', function (Blueprint $table) {
                    $table->dropForeign(['idea_challenge_id']);
                });
            }

            // Drop columns if they exist
            Schema::table('teams', function (Blueprint $table) {
                if (Schema::hasColumn('teams', 'idea_path_id')) {
                    $table->dropColumn('idea_path_id');
                }
            });
            Schema::table('teams', function (Blueprint $table) {
                if (Schema::hasColumn('teams', 'idea_challenge_id')) {
                    $table->dropColumn('idea_challenge_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
