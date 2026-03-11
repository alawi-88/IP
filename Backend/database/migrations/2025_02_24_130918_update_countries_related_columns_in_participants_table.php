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
                if (Schema::hasTable('participants')) {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'participants' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('participants_country_id_foreign', $__fkNames)) {
            Schema::table('participants', fn(Blueprint $t) => $t->dropForeign(['country_id']));
        }
        if (in_array('participants_nationality_id_foreign', $__fkNames)) {
            Schema::table('participants', fn(Blueprint $t) => $t->dropForeign(['nationality_id']));
        }
        if (in_array('participants_residence_city_id_foreign', $__fkNames)) {
            Schema::table('participants', fn(Blueprint $t) => $t->dropForeign(['residence_city_id']));
        }

            Schema::table('participants', function (Blueprint $table) {
            if (!Schema::hasColumn('participants', 'nationality_id')) { $table->foreignId('nationality_id') }
                ->nullable()
                
                ->constrained('nationalities');
            if (!Schema::hasColumn('participants', 'country_id')) { $table->foreignId('country_id') }
                ->nullable()
                
                ->constrained('countries');
            if (!Schema::hasColumn('participants', 'residence_city_id')) { $table->foreignId('residence_city_id') }
                ->nullable()
                
                ->constrained('cities');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
if (Schema::hasColumn('participants', 'nationality_id')) { $table->dropColumn('nationality_id'); }
if (Schema::hasColumn('participants', 'country_id')) { $table->dropColumn('country_id'); }
if (Schema::hasColumn('participants', 'residence_city_id')) { $table->dropColumn('residence_city_id'); }
        });
        }
    }
};
