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
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->json('assessment_scores')->nullable()->after('status')->comment('JSON object storing scores for each assessment criterion');
            $table->unsignedInteger('total_score')->nullable()->after('assessment_scores')->comment('Total calculated score from all criteria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropColumn(['assessment_scores', 'total_score']);
        });
    }
};

