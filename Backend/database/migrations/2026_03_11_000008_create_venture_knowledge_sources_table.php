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
        Schema::create('venture_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['industry_report', 'market_data', 'template', 'methodology']);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_knowledge_sources');
    }
};
