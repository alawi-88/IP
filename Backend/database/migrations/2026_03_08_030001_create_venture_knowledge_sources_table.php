<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venture_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->longText('content')->comment('The knowledge text to inject into AI prompts');
            $table->enum('type', ['text', 'document', 'url'])->default('text');
            $table->string('file_path', 500)->nullable()->comment('For uploaded documents (future use)');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0)->comment('Higher = injected first');
            $table->unsignedInteger('max_tokens')->default(500)->comment('Max characters to inject per source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venture_knowledge_sources');
    }
};
