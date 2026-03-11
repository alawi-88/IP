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
        Schema::create('venture_section_configs', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('tab_key')->index();
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('component_type')->default('text_content');
            $table->integer('display_order')->default(0);
            $table->longText('default_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_section_configs');
    }
};
