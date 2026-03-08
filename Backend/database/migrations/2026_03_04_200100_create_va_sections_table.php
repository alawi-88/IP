<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('va_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('startup_id')->constrained('startups')->onDelete('cascade');
            $table->enum('section_key', [
                'foundation',
                'strategic_frameworks',
                'path_to_mvp',
                'gtm_strategy',
                'competitive_analysis'
            ]);
            $table->string('title_en');
            $table->string('title_ar');
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
            
            $table->index('startup_id');
            $table->unique(['startup_id', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('va_sections');
    }
};
