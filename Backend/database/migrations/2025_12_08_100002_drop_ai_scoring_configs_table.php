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
        Schema::dropIfExists('ai_scoring_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ai_scoring_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_form_config_id')->constrained()->onDelete('cascade');
            $table->boolean('ai_scoring_enabled')->default(false);
            $table->string('ai_provider')->default('openai')->comment('openai, anthropic, etc.');
            $table->string('ai_model')->default('gpt-4')->comment('Model identifier');
            $table->decimal('temperature', 3, 2)->default(0.3)->comment('AI temperature (0-2)');
            $table->integer('max_tokens')->default(2000);
            $table->text('system_prompt')->nullable()->comment('Custom system prompt for AI');
            $table->json('scoring_rules')->nullable()->comment('Additional scoring rules and guidelines');
            $table->boolean('auto_approve_threshold_enabled')->default(false);
            $table->integer('auto_approve_min_score')->nullable()->comment('Minimum score for auto-approval');
            $table->decimal('confidence_threshold', 3, 2)->default(0.7)->comment('Minimum confidence for AI scores (0-1)');
            $table->boolean('require_manual_review_low_confidence')->default(true);
            $table->boolean('log_ai_decisions')->default(true);
            $table->json('metadata')->nullable()->comment('Additional configuration metadata');
            $table->timestamps();
            
            $table->index('registration_form_config_id');
        });
    }
};

