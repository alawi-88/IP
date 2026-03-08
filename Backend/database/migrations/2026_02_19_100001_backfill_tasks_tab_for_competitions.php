<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Competition;
use App\Models\CompetitionTab;

return new class extends Migration
{
    /**
     * Backfill 'tasks' tab for all existing competitions that don't have it.
     */
    public function up(): void
    {
        $competitions = Competition::all();
        foreach ($competitions as $competition) {
            CompetitionTab::firstOrCreate(
                [
                    'competition_id' => $competition->id,
                    'tab' => 'tasks',
                ],
                [
                    'is_visible' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CompetitionTab::where('tab', 'tasks')->delete();
    }
};
