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
                if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('competition_applications', 'assessment_scores')) { $table->json('assessment_scores')->nullable()->comment('JSON object storing scores for each assessment criterion'); }
            if (!Schema::hasColumn('competition_applications', 'total_score')) { $table->unsignedInteger('total_score')->nullable()->comment('Total calculated score from all criteria'); }
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
                            if (Schema::hasColumn('competition_applications', 'assessment_scores')) { $table->dropColumn('assessment_scores'); }
                if (Schema::hasColumn('competition_applications', 'total_score')) { $table->dropColumn('total_score'); }
        });
        }
    }
};

