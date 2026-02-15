<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            INSERT INTO competition_tabs (competition_id, tab, is_visible, created_at, updated_at)
            SELECT competitions.id, 'leaderboard', 1, NOW(), NOW()
            FROM competitions
            WHERE NOT EXISTS (
                SELECT 1
                FROM competition_tabs
                WHERE competition_tabs.competition_id = competitions.id
                  AND competition_tabs.tab = 'leaderboard'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('competition_tabs')
            ->where('tab', 'leaderboard')
            ->delete();
    }
};
