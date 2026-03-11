<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
                if (Schema::hasTable('form_evaluation_scores')) {
            Schema::table('form_evaluation_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('form_evaluation_scores', 'exclude_from_calculation')) { $table->boolean('exclude_from_calculation')->default(false); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('form_evaluation_scores')) {
            Schema::table('form_evaluation_scores', function (Blueprint $table) {
            if (Schema::hasColumn('form_evaluation_scores', 'exclude_from_calculation')) { $table->dropColumn('exclude_from_calculation'); }
        });
        }
    }
};
