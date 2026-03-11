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
                if (Schema::hasTable('teams')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('teams_track_id_foreign', $__fkNames)) {
            Schema::table('teams', fn(Blueprint $t) => $t->dropForeign(['track_id']));
        }
        if (in_array('teams_sub_track_id_foreign', $__fkNames)) {
            Schema::table('teams', fn(Blueprint $t) => $t->dropForeign(['sub_track_id']));
        }

            Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'track_id')) {
                $table->unsignedBigInteger('track_id')->nullable();
            }
            if (!Schema::hasColumn('teams', 'sub_track_id')) {
                $table->unsignedBigInteger('sub_track_id')->nullable();
            }
        });
        // Add foreign keys separately (tracks table may not exist in fresh migration order)
        if (Schema::hasTable('tracks') && Schema::hasColumn('teams', 'track_id')) {
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks);
            if (!in_array('teams_track_id_foreign', $fkNames)) {
                Schema::table('teams', fn(Blueprint $t) => $t->foreign('track_id')->references('id')->on('tracks')->nullOnDelete());
            }
        }
        if (Schema::hasTable('sub_tracks') && Schema::hasColumn('teams', 'sub_track_id')) {
            $fks2 = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $fkNames2 = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks2);
            if (!in_array('teams_sub_track_id_foreign', $fkNames2)) {
                Schema::table('teams', fn(Blueprint $t) => $t->foreign('sub_track_id')->references('id')->on('sub_tracks')->nullOnDelete());
            }
        }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {

                if (Schema::hasColumn('teams', 'track_id')) { $table->dropColumn('track_id'); }
                if (Schema::hasColumn('teams', 'sub_track_id')) { $table->dropColumn('sub_track_id'); }
        });
        }
    }
};
