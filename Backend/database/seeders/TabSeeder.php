<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramTab;
use Illuminate\Database\Seeder;

class TabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = Program::all();

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

        foreach ($programs as $program) {
            foreach ($tabs as $tab) {
                ProgramTab::updateOrCreate(
                    ['program_id' => $program->id, 'tab' => $tab],
                    ['is_visible' => fake()->boolean()]
                );
            }
        }
    }
}
