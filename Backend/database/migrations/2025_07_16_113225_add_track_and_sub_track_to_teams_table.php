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
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('track_id')->nullable(); // Adjust placement as needed
            $table->unsignedBigInteger('sub_track_id')->nullable();

            // Optional: Add foreign key constraints
            $table->foreign('track_id')->references('id')->on('tracks')->nullOnDelete();
            $table->foreign('sub_track_id')->references('id')->on('sub_tracks')->nullOnDelete();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['track_id']);
            $table->dropForeign(['sub_track_id']);
            $table->dropColumn(['track_id', 'sub_track_id']);
        });
        }
    }
};
