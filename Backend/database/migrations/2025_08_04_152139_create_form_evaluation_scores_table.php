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
        Schema::create('form_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_project_id')->constrained()->onDelete('cascade');
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('stage_id')->constrained()->onDelete('cascade');
            $table->decimal('evaluation_score', 8, 2);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_evaluation_scores');
    }
};
