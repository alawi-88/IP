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
        Schema::create('form_ai_enhancement_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->boolean('ai_enhancement_enabled')->default(false)->comment('Enable AI enhancement for form submissions');
            $table->json('ai_enhancement_fields')->nullable()->comment('Array of field slugs with instructions that should be enhanced');
            $table->timestamps();
            
            $table->index('form_id');
            $table->unique('form_id'); // One enhancement config per form
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_ai_enhancement_configs');
    }
};
