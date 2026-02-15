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
        Schema::create('mentor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->enum('video_tool', ['zoom', 'teams', 'google_meet'])->nullable();
            $table->string('meeting_id')->nullable(); // External meeting ID
            $table->string('join_url')->nullable(); // Meeting join URL
            $table->string('password')->nullable(); // Meeting password if required
            $table->json('calendar_event_id')->nullable(); // Calendar event IDs (multiple calendars)
            $table->text('notes')->nullable(); // Mentor notes
            $table->text('feedback')->nullable(); // Post-session feedback
            $table->integer('rating')->nullable(); // Session rating (1-5)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['mentor_id', 'scheduled_at']);
            $table->index(['participant_id', 'scheduled_at']);
            $table->index(['competition_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['video_tool', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_sessions');
    }
};
