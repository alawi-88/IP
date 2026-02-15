<?php

namespace Database\Seeders;

use App\Models\CompetitionTab;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RemoveStageTab extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompetitionTab::where('tab', 'stages')->delete();
    }
}
