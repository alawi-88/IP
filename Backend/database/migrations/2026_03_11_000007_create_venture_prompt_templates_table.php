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
        Schema::create('venture_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('section_slug')->index();
            $table->string('label');
            $table->longText('system_prompt')->nullable();
            $table->longText('user_prompt')->nullable();
            $table->json('json_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_tokens')->default(4096);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_prompt_templates');
    }
};
