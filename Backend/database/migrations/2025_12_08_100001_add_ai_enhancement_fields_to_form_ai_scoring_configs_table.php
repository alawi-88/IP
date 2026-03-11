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
                if (Schema::hasTable('form_ai_scoring_configs')) {
            Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            $table->boolean('ai_enhancement_enabled')->default(false)->comment('Enable AI enhancement for form submissions');
            $table->text('ai_enhancement_context')->nullable()->comment('Form-level context for AI enhancement');
            $table->text('ai_enhancement_instructions')->nullable()->comment('Instructions for AI enhancement');
            $table->json('ai_enhancement_fields')->nullable()->comment('Array of field slugs that should be enhanced');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('form_ai_scoring_configs')) {
            Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            try {                 if (Schema::hasColumn('form_ai_scoring_configs', 'ai_enhancement_enabled')) { $table->dropColumn('ai_enhancement_enabled'); }
                if (Schema::hasColumn('form_ai_scoring_configs', 'ai_enhancement_context')) { $table->dropColumn('ai_enhancement_context'); }
                if (Schema::hasColumn('form_ai_scoring_configs', 'ai_enhancement_instructions')) { $table->dropColumn('ai_enhancement_instructions'); }
                if (Schema::hasColumn('form_ai_scoring_configs', 'ai_enhancement_fields')) { $table->dropColumn('ai_enhancement_fields'); } } catch (\Exception $e) {}
        });
        }
    }
};


