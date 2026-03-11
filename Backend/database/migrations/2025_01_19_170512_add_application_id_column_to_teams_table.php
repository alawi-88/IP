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
        if (in_array('teams_application_id_foreign', $__fkNames)) {
            Schema::table('teams', fn(Blueprint $t) => $t->dropForeign(['application_id']));
        }

            Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('application_id')
                
                ->constrained('competition_applications')
                ->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
if (Schema::hasColumn('teams', 'application_id')) { $table->dropColumn('application_id'); }
        });
        }
    }
};
