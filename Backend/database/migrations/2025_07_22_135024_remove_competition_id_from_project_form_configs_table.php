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
                if (Schema::hasTable('project_form_configs')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_form_configs' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('project_form_configs_competition_id_foreign', $__fkNames)) {
            Schema::table('project_form_configs', fn(Blueprint $t) => $t->dropForeign(['competition_id']));
        }

            Schema::table('project_form_configs', function (Blueprint $table) {
if (Schema::hasColumn('project_form_configs', 'competition_id')) { $table->dropColumn('competition_id'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_form_configs')) {
            Schema::table('project_form_configs', function (Blueprint $table) {
            //
        });
        }
    }
};
