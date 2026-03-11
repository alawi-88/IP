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
                if (Schema::hasTable('team_members')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_members' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('team_members_participant_id_foreign', $__fkNames)) {
            Schema::table('team_members', fn(Blueprint $t) => $t->dropForeign(['participant_id']));
        }

            Schema::table('team_members', function (Blueprint $table) {
            if (!Schema::hasColumn('team_members', 'participant_id')) { $table->foreignId('participant_id')->constrained()->cascadeOnDelete(); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
if (Schema::hasColumn('team_members', 'participant_id')) { $table->dropColumn('participant_id'); }
        });
        }
    }
};
