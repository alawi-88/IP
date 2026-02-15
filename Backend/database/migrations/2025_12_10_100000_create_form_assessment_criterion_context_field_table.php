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
        if (Schema::hasTable('form_assessment_criterion_context_field')) {
            return;
        }

        Schema::create('form_assessment_criterion_context_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_assessment_criterion_id')
                ->constrained('form_assessment_criteria')
                ->onDelete('cascade');
            $table->foreignId('form_field_id')
                ->constrained('form_fields')
                ->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate contextual field mappings
            $table->unique(['form_assessment_criterion_id', 'form_field_id'], 'unique_criterion_context_field_mapping');

            // Indexes for performance
            $table->index('form_assessment_criterion_id');
            $table->index('form_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_assessment_criterion_context_field');
    }
};

