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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['claude', 'openai', 'gemini']);
            $table->text('api_key')->encrypted();
            $table->string('model_name');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('max_tokens')->default(4096);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
