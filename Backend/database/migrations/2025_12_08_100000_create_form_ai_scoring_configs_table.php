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
        Schema::create('form_ai_scoring_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->text('ai_prompt')->nullable()->comment('AI prompt defining role and assessment context');
            $table->unsignedInteger('total_weight')->default(100)->comment('Total weight for this form (e.g., 100)');
            $table->timestamps();
            
            $table->index('form_id');
            $table->unique('form_id'); // One config per form
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_ai_scoring_configs');
    }
};

