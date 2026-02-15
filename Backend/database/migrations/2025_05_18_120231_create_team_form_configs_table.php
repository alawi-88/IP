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
        Schema::create('team_form_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');

            // Core settings
            $table->boolean('is_active')->default(false);

            // Team size
            $table->unsignedTinyInteger('min_team_members')->default(2);
            $table->unsignedTinyInteger('max_team_members')->default(6);

            // Track settings
            $table->boolean('allow_track_selection')->default(false);
            $table->boolean('require_same_track')->default(false);

            // Publishing
            $table->boolean('auto_publish_teams')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_form_configs');
    }
};
