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
                if (Schema::hasTable('mentors')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mentors' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('mentors_track_id_foreign', $__fkNames)) {
            Schema::table('mentors', fn(Blueprint $t) => $t->dropForeign(['track_id']));
        }

            Schema::table('mentors', function (Blueprint $table) {
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('tracks')
                ->nullOnDelete()
                ;
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
if (Schema::hasColumn('mentors', 'track_id')) { $table->dropColumn('track_id'); }
        });
        }
    }
};
