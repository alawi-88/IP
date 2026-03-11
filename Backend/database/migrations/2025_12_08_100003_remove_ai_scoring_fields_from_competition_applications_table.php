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

try {                 if (Schema::hasColumn('competition_applications', 'ai_scored')) { $table->dropColumn('ai_scored'); }
                if (Schema::hasColumn('competition_applications', 'ai_scores')) { $table->dropColumn('ai_scores'); }
                if (Schema::hasColumn('competition_applications', 'ai_confidence')) { $table->dropColumn('ai_confidence'); }
                if (Schema::hasColumn('competition_applications', 'ai_reasoning')) { $table->dropColumn('ai_reasoning'); }
                if (Schema::hasColumn('competition_applications', 'ai_score_overridden')) { $table->dropColumn('ai_score_overridden'); }
                if (Schema::hasColumn('competition_applications', 'ai_scored_by')) { $table->dropColumn('ai_scored_by'); }
                if (Schema::hasColumn('competition_applications', 'ai_scored_at')) { $table->dropColumn('ai_scored_at'); }
                if (Schema::hasColumn('competition_applications', 'ai_overridden_by')) { $table->dropColumn('ai_overridden_by'); }
                if (Schema::hasColumn('competition_applications', 'ai_overridden_at')) { $table->dropColumn('ai_overridden_at'); }
                if (Schema::hasColumn('competition_applications', 'ai_metadata')) { $table->dropColumn('ai_metadata'); } } catch (\Exception $e) {}
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
            $table->boolean('ai_scored')->default(false)->comment('Whether this application was scored by AI');
            $table->json('ai_scores')->nullable()->comment('AI-generated scores before manual override');
            $table->decimal('ai_confidence', 3, 2)->nullable()->comment('AI confidence score (0-1)');
            $table->text('ai_reasoning')->nullable()->comment('AI explanation for scores');
            $table->boolean('ai_score_overridden')->default(false)->comment('Whether admin overrode AI scores');
            $table->foreignId('ai_scored_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who triggered AI scoring');
            $table->timestamp('ai_scored_at')->nullable()->comment('When AI scoring was performed');
            $table->foreignId('ai_overridden_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who overrode AI scores');
            $table->timestamp('ai_overridden_at')->nullable()->comment('When AI scores were overridden');
            $table->json('ai_metadata')->nullable()->comment('Additional AI scoring metadata');
            
            $table->index('ai_scored');
            $table->index('ai_score_overridden');
        });
        }
    }
};

