<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('va_page_id')->constrained('va_pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('field_key');
            $table->longText('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->enum('status', ['pending', 'completed', 'accepted', 'modified', 'dismissed'])->default('pending');
            $table->string('model_used')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('generation_time_ms')->nullable();
            $table->timestamps();
            
            $table->index('va_page_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
