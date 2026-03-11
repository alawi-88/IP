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
            // Drop existing foreign keys on track_id/sub_track_id if they exist
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $fks);

            if (in_array('teams_track_id_foreign', $fkNames)) {
                Schema::table('teams', fn(Blueprint $t) => $t->dropForeign(['track_id']));
            }
            if (in_array('teams_sub_track_id_foreign', $fkNames)) {
                Schema::table('teams', fn(Blueprint $t) => $t->dropForeign(['sub_track_id']));
            }

            // Add columns if they don't exist, make nullable if they do
            if (!Schema::hasColumn('teams', 'track_id')) {
                Schema::table('teams', function (Blueprint $table) {
                    $table->unsignedBigInteger('track_id')->nullable();
                });
            } else {
                Schema::table('teams', function (Blueprint $table) {
                    $table->unsignedBigInteger('track_id')->nullable()->change();
                });
            }

            if (!Schema::hasColumn('teams', 'sub_track_id')) {
                Schema::table('teams', function (Blueprint $table) {
                    $table->unsignedBigInteger('sub_track_id')->nullable();
                });
            }

            // Add foreign keys to tracks/sub_tracks tables if they exist
            if (Schema::hasTable('tracks')) {
                Schema::table('teams', fn(Blueprint $t) => $t->foreign('track_id')->references('id')->on('tracks')->nullOnDelete());
            }
            if (Schema::hasTable('sub_tracks') && Schema::hasColumn('teams', 'sub_track_id')) {
                Schema::table('teams', fn(Blueprint $t) => $t->foreign('sub_track_id')->references('id')->on('sub_tracks')->nullOnDelete());
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
