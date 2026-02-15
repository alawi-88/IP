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
        Schema::create('competition_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');

            // Step 1: Basic Information
            $table->boolean('has_team');
            $table->string('team_name')->nullable();
            $table->string('team_logo')->nullable();
            $table->text('team_strength')->nullable();

            // Step 2: The Idea
            $table->boolean('has_idea');
            $table->foreignId('track_id')->constrained('paths')->onDelete('cascade');
            $table->foreignId('idea_challenge_id')->constrained('challenges')->onDelete('cascade');
            $table->text('idea_description')->nullable();

            // Step 3: Confirmation
            $table->text('participation_interest');
            $table->boolean('team_member_previous_participation');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_applications');
    }
};
