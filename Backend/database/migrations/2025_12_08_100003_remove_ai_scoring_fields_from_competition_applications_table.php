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
            // Drop foreign keys first
            $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'competition_applications' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);

            if (in_array('competition_applications_ai_scored_by_foreign', $__fkNames)) {
                Schema::table('competition_applications', fn(Blueprint $t) => $t->dropForeign(['ai_scored_by']));
            }
            if (in_array('competition_applications_ai_overridden_by_foreign', $__fkNames)) {
                Schema::table('competition_applications', fn(Blueprint $t) => $t->dropForeign(['ai_overridden_by']));
            }

            // Drop columns one by one in separate closures
            $columns = ['ai_scored', 'ai_scores', 'ai_confidence', 'ai_reasoning', 'ai_score_overridden', 'ai_scored_by', 'ai_scored_at', 'ai_overridden_by', 'ai_overridden_at', 'ai_metadata'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('competition_applications', $col)) {
                    Schema::table('competition_applications', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
                $table->boolean('ai_scored')->default(false);
                $table->json('ai_scores')->nullable();
                $table->decimal('ai_confidence', 3, 2)->nullable();
                $table->text('ai_reasoning')->nullable();
                $table->boolean('ai_score_overridden')->default(false);
                $table->foreignId('ai_scored_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('ai_scored_at')->nullable();
                $table->foreignId('ai_overridden_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('ai_overridden_at')->nullable();
                $table->json('ai_metadata')->nullable();
            });
        }
    }
};
