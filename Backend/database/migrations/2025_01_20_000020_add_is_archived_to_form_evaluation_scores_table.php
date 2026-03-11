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
            if (!Schema::hasColumn('form_evaluation_scores', 'is_archived')) { $table->boolean('is_archived')->default(false); }
            if (!Schema::hasColumn('form_evaluation_scores', 'archived_at')) { $table->timestamp('archived_at')->nullable(); }
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
                            if (Schema::hasColumn('form_evaluation_scores', 'is_archived')) { $table->dropColumn('is_archived'); }
                if (Schema::hasColumn('form_evaluation_scores', 'archived_at')) { $table->dropColumn('archived_at'); }
        });
        }
    }
};
