<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venture_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 80)->unique()->comment('__system__ for global system prompt, or section key like about, swot_analysis, etc.');
            $table->longText('system_prompt')->nullable()->comment('Override for global system prompt (only used when section_key=__system__)');
            $table->longText('section_prompt')->nullable()->comment('Override for section-specific task prompt');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venture_prompt_templates');
    }
};
