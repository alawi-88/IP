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
        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_form_config_id')->constrained()->cascadeOnDelete();
            $table->text('description')->comment('Description of what is being scored');
            $table->unsignedInteger('max_score')->comment('Maximum score for this criterion');
            $table->unsignedInteger('sort_order')->default(0)->comment('Order in which criteria are displayed');
            $table->timestamps();

            // Index for better query performance
            $table->index(['registration_form_config_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_criteria');
    }
};

