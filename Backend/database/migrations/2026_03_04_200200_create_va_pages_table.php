<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('va_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('va_section_id')->constrained('va_sections')->onDelete('cascade');
            $table->string('page_key');
            $table->string('title_en');
            $table->string('title_ar');
            $table->json('content')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->integer('order')->default(0);
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamp('auto_saved_at')->nullable();
            $table->timestamps();
            
            $table->index('va_section_id');
            $table->index('status');
            $table->unique(['va_section_id', 'page_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('va_pages');
    }
};
