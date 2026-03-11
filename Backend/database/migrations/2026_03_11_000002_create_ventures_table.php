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
        Schema::create('ventures', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('idea_prompt')->nullable();
            $table->enum('status', ['draft', 'generating', 'completed', 'failed'])->default('draft');
            $table->integer('viability_score')->nullable();
            $table->json('viability_breakdown')->nullable();
            $table->string('industry')->nullable();
            $table->string('target_market')->nullable();
            $table->string('business_model')->nullable();
            $table->integer('sections_total')->default(0);
            $table->integer('sections_completed')->default(0);
            $table->integer('sections_failed')->default(0);
            $table->unsignedBigInteger('competition_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('generation_started_at')->nullable();
            $table->timestamp('generation_completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['created_by']);
            $table->index(['competition_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventures');
    }
};
