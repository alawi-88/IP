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
        if (Schema::hasTable('contact_us')) {
            // Drop foreign key if it exists
            $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_us' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
            if (in_array('contact_us_participant_id_foreign', $__fkNames)) {
                Schema::table('contact_us', fn(Blueprint $t) => $t->dropForeign(['participant_id']));
            }

            Schema::table('contact_us', function (Blueprint $table) {
                if (Schema::hasColumn('contact_us', 'participant_id') && !Schema::hasColumn('contact_us', 'model_id')) {
                    $table->renameColumn('participant_id', 'model_id');
                }
            });

            Schema::table('contact_us', function (Blueprint $table) {
                if (!Schema::hasColumn('contact_us', 'model_type')) {
                    $table->string('model_type')->nullable();
                }
            });

            // Add morph index if not exists
            $__indexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_us' AND INDEX_NAME = 'contact_us_morph_idx'");
            if (empty($__indexes)) {
                Schema::table('contact_us', function (Blueprint $table) {
                    if (Schema::hasColumn('contact_us', 'model_type') && Schema::hasColumn('contact_us', 'model_id')) {
                        $table->index(['model_type', 'model_id'], 'contact_us_morph_idx');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            if (Schema::hasColumn('contact_us', 'model_type')) { $table->dropColumn('model_type'); }

            if (Schema::hasColumn('contact_us', 'model_id')) { $table->renameColumn('model_id', 'participant_id'); }

            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
        });
        }
    }
};
