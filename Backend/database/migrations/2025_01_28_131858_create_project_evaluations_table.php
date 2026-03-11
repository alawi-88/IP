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
        Schema::create('project_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_project_id')->constrained()->onDelete('cascade'); // assignment id
            $table->foreignId('path_id')->constrained()->onDelete('cascade');
            $table->string('question');
            $table->integer('answer');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_evaluations');
    }
};
