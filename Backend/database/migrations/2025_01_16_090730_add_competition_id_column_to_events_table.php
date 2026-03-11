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
                if (Schema::hasTable('events')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('events_competition_id_foreign', $__fkNames)) {
            Schema::table('events', fn(Blueprint $t) => $t->dropForeign(['competition_id']));
        }

            Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'competition_id')) { $table->foreignId('competition_id')->constrained()->onDelete('cascade'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
if (Schema::hasColumn('events', 'competition_id')) { $table->dropColumn('competition_id'); }
        });
        }
    }
};
