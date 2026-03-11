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
                if (Schema::hasTable('project_evaluations')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_evaluations' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('project_evaluations_path_id_foreign', $__fkNames)) {
            Schema::table('project_evaluations', fn(Blueprint $t) => $t->dropForeign(['path_id']));
        }

            Schema::table('project_evaluations', function (Blueprint $table) {
if (Schema::hasColumn('project_evaluations', 'path_id')) { $table->dropColumn('path_id'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
            //
        });
        }
    }
};
