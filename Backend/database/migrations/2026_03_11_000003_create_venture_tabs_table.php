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
        Schema::create('venture_tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venture_id')->constrained('ventures')->cascadeOnDelete();
            $table->string('tab_key');
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['venture_id', 'tab_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_tabs');
    }
};
