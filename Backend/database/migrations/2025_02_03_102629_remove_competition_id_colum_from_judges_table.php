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
                if (Schema::hasTable('judges')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'judges' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('judges_competition_id_foreign', $__fkNames)) {
            Schema::table('judges', fn(Blueprint $t) => $t->dropForeign(['competition_id']));
        }

            Schema::table('judges', function (Blueprint $table) {
if (Schema::hasColumn('judges', 'competition_id')) { $table->dropColumn('competition_id'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('judges')) {
            Schema::table('judges', function (Blueprint $table) {
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
        });
        }
    }
};
