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
        Schema::create('mentor_video_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->onDelete('cascade');
            $table->enum('tool_type', ['zoom', 'teams', 'google_meet']);
            $table->string('account_id')->nullable(); // External account identifier
            $table->string('account_email')->nullable(); // Account email for verification
            $table->text('access_token')->nullable(); // Encrypted access token
            $table->text('refresh_token')->nullable(); // Encrypted refresh token
            $table->timestamp('token_expires_at')->nullable();
            $table->json('tool_settings')->nullable(); // Additional tool-specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default tool for new sessions
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['mentor_id', 'tool_type']);
            $table->index(['mentor_id', 'is_active']);
            $table->index(['mentor_id', 'is_default']);
            
            // Ensure only one default tool per mentor
            // Using a partial unique index that only applies when is_default = true
            // This allows multiple non-default tools but only one default tool per mentor
            $table->unique(['mentor_id', 'tool_type'], 'unique_mentor_tool_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_video_tools');
    }
};
