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
        Schema::create('form_assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Criteria name');
            $table->text('description')->comment('Criteria description');
            $table->text('instruction')->nullable()->comment('What the agent should do for this criterion');
            $table->unsignedInteger('weight')->comment('Weight for this criterion (portion of total weight)');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->unsignedInteger('sort_order')->default(0)->comment('Order in which criteria are displayed');
            $table->timestamps();
            
            $table->index(['form_id', 'status']);
            $table->index(['form_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_assessment_criteria');
    }
};

