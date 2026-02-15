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
        Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            $table->boolean('ai_enhancement_enabled')->default(false)->after('total_weight')->comment('Enable AI enhancement for form submissions');
            $table->text('ai_enhancement_context')->nullable()->after('ai_enhancement_enabled')->comment('Form-level context for AI enhancement');
            $table->text('ai_enhancement_instructions')->nullable()->after('ai_enhancement_context')->comment('Instructions for AI enhancement');
            $table->json('ai_enhancement_fields')->nullable()->after('ai_enhancement_instructions')->comment('Array of field slugs that should be enhanced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enhancement_enabled',
                'ai_enhancement_context',
                'ai_enhancement_instructions',
                'ai_enhancement_fields',
            ]);
        });
    }
};


