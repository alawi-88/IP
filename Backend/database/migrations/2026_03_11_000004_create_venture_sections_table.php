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
            $table->unsignedBigInteger('venture_id');
            $table->foreignId('venture_tab_id')->constrained('venture_tabs')->cascadeOnDelete();
            $table->string('slug');
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->json('content')->nullable();
            $table->json('content_ar')->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('generation_attempts')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->string('component_type')->nullable();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->integer('tokens_used')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['venture_id']);
            $table->index(['venture_tab_id', 'sort_order']);
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
