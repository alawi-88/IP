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
        Schema::create('project_evaluation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('project_evaluation_id')->constrained('project_evaluations')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->enum('type', [
                'general_feedback',
                'issue_detected',
                'administrative_decision',
                'other'
            ])->default('general_feedback');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_evaluation_notes');
    }
};
