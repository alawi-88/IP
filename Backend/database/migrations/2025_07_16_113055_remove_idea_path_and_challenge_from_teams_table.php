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
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                // Drop foreign keys if they exist
                try { $table->dropForeign(['idea_path_id']); } catch (\Exception $e) {}
                try { $table->dropForeign(['idea_challenge_id']); } catch (\Exception $e) {}
            });

            Schema::table('teams', function (Blueprint $table) {
                if (Schema::hasColumn('teams', 'idea_path_id')) {
                    $table->dropColumn('idea_path_id');
                }
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
