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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            // Step 1: Basic Information
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->text('strength')->nullable();

            // Step 2: The Idea
            $table->foreignId('track_id')->constrained('paths')->onDelete('cascade');
            $table->foreignId('idea_challenge_id')->constrained('challenges')->onDelete('cascade');
            $table->text('idea_description')->nullable();

            // Step 3: Confirmation
            $table->boolean('team_member_previous_participation');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
