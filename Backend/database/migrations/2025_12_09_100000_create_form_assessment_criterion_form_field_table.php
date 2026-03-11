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
        if (Schema::hasTable('form_assessment_criterion_form_field')) {
            return;
        }

        Schema::create('form_assessment_criterion_form_field', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_assessment_criterion_id');
            $table->foreign('form_assessment_criterion_id', 'fac_ff_criterion_id_fk')
                ->references('id')->on('form_assessment_criteria')
                ->onDelete('cascade');
            $table->unsignedBigInteger('form_field_id');
            $table->foreign('form_field_id', 'fac_ff_field_id_fk')
                ->references('id')->on('form_fields')
                ->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate mappings
            $table->unique(['form_assessment_criterion_id', 'form_field_id'], 'unique_criterion_field_mapping');

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
        Schema::dropIfExists('form_assessment_criterion_form_field');
    }
};
