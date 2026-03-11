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
        Schema::create('venture_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venture_section_id')->constrained('venture_sections')->cascadeOnDelete();
            $table->json('content');
            $table->json('content_ar')->nullable();
            $table->integer('version_number');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_versions');
    }
};
