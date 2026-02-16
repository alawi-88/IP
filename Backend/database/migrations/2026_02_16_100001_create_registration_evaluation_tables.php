<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Epic IN-2029: Admin Registration Evaluation Framework
     * Creates tables for multi-evaluator registration evaluation system
     */
    public function up(): void
    {
        // Registration evaluation forms - multiple forms per competition for different dimensions
        Schema::create('registration_evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->json('name'); // bilingual EN/AR
            $table->json('description')->nullable(); // bilingual EN/AR
            $table->string('dimension')->nullable();
            $table->string('scoring_scale')->default('1-10');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['competition_id', 'status']);
        });

        // Evaluation criteria within each evaluation form
        Schema::create('registration_evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registration_evaluation_form_id');
            $table->json('name'); // bilingual EN/AR
            $table->json('description')->nullable(); // bilingual EN/AR
            $table->unsignedInteger('max_score')->default(10);
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('registration_evaluation_form_id', 'reg_eval_crit_form_fk')
                ->references('id')->on('registration_evaluation_forms')->cascadeOnDelete();
            $table->index('registration_evaluation_form_id', 'reg_eval_criteria_form_idx');
        });

        // Evaluators assigned to competitions (admin users who evaluate registrations)
        Schema::create('registration_evaluators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['competition_id', 'user_id']);
        });

        // Section/form allocations per evaluator
        Schema::create('registration_evaluator_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('registration_evaluator_id');
            $table->unsignedBigInteger('registration_evaluation_form_id');
            $table->timestamps();

            $table->foreign('registration_evaluator_id', 'reg_eval_sec_evaluator_fk')
                ->references('id')->on('registration_evaluators')->cascadeOnDelete();
            $table->foreign('registration_evaluation_form_id', 'reg_eval_sec_form_fk')
                ->references('id')->on('registration_evaluation_forms')->cascadeOnDelete();
            $table->unique(
                ['registration_evaluator_id', 'registration_evaluation_form_id'],
                'reg_eval_section_unique'
            );
        });

        // Individual evaluations - scores given by evaluators for each application
        Schema::create('registration_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_application_id');
            $table->unsignedBigInteger('registration_evaluator_id');
            $table->unsignedBigInteger('registration_evaluation_form_id');
            $table->unsignedBigInteger('registration_evaluation_criterion_id');
            $table->unsignedInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('competition_application_id', 'reg_eval_app_fk')
                ->references('id')->on('competition_applications')->cascadeOnDelete();
            $table->foreign('registration_evaluator_id', 'reg_eval_evaluator_fk')
                ->references('id')->on('registration_evaluators')->cascadeOnDelete();
            $table->foreign('registration_evaluation_form_id', 'reg_eval_form_fk')
                ->references('id')->on('registration_evaluation_forms')->cascadeOnDelete();
            $table->foreign('registration_evaluation_criterion_id', 'reg_eval_criterion_fk')
                ->references('id')->on('registration_evaluation_criteria')->cascadeOnDelete();

            $table->unique(
                ['competition_application_id', 'registration_evaluator_id', 'registration_evaluation_criterion_id'],
                'reg_eval_unique_score'
            );
            $table->index('competition_application_id', 'reg_eval_app_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_evaluations');
        Schema::dropIfExists('registration_evaluator_sections');
        Schema::dropIfExists('registration_evaluators');
        Schema::dropIfExists('registration_evaluation_criteria');
        Schema::dropIfExists('registration_evaluation_forms');
    }
};
