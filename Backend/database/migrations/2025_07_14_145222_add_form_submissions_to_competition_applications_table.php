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
        if (in_array('competition_applications_form_id_foreign', $__fkNames)) {
            Schema::table('competition_applications', fn(Blueprint $t) => $t->dropForeign(['form_id']));
        }

            Schema::table('competition_applications', function (Blueprint $table) {
            $table->foreignId('form_id')->constrained('forms')->onDelete('cascade');
            $table->json('form_submissions')->nullable();
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
            if (Schema::hasColumn('competition_applications', 'form_id')) { $table->dropColumn('form_id'); }
            if (Schema::hasColumn('competition_applications', 'form_submissions')) { $table->dropColumn('form_submissions'); }
        });
        }
    }
};
