<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionTab;
use Illuminate\Database\Seeder;

class TabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competitions = Competition::all();

        $tabs = [
            'teams',
            'my-team',
            'events',
            'mentors',
            'guidelines',
            'projects',
            'stages',
            'leaderboard'
        ];

        foreach ($competitions as $competition) {
            foreach ($tabs as $tab) {
                CompetitionTab::updateOrCreate(
                    ['competition_id' => $competition->id, 'tab' => $tab],
                    ['is_visible' => fake()->boolean()]
                );
            }
        }
    }
}
