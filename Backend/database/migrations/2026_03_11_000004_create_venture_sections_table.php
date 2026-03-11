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
        Schema::create('venture_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venture_tab_id')->constrained('venture_tabs')->cascadeOnDelete();
            $table->string('section_key');
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->json('content')->nullable();
            $table->json('content_ar')->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('generation_attempts')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_sections');
    }
};
