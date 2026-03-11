<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
                if (Schema::hasTable('competition_applications')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'competition_applications' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('competition_applications_track_id_foreign', $__fkNames)) {
            Schema::table('competition_applications', fn(Blueprint $t) => $t->dropForeign(['track_id']));
        }
        if (in_array('competition_applications_idea_challenge_id_foreign', $__fkNames)) {
            Schema::table('competition_applications', fn(Blueprint $t) => $t->dropForeign(['idea_challenge_id']));
        }

            Schema::table('competition_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('competition_applications', 'track_id')) { $table->foreignId('track_id')->nullable()->constrained('paths')->cascadeOnDelete(); }
            if (!Schema::hasColumn('competition_applications', 'idea_challenge_id')) { $table->foreignId('idea_challenge_id')->nullable()->constrained('challenges')->cascadeOnDelete(); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
if (Schema::hasColumn('competition_applications', 'track_id')) { $table->dropColumn('track_id'); }
if (Schema::hasColumn('competition_applications', 'idea_challenge_id')) { $table->dropColumn('idea_challenge_id'); }
        });
        }
    }
};
