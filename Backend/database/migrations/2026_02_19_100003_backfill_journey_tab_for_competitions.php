<?php

use App\Models\Competition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Backfill the 'journey' tab for all existing competitions.
     * Fix #10: Program journey in participation hub.
     */
    public function up(): void
    {
        Competition::all()->each(function ($competition) {
            $competition->tabs()->updateOrCreate(
                ['tab' => 'journey'],
                ['is_visible' => true]
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\CompetitionTab::where('tab', 'journey')->delete();
    }
};
