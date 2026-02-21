<?php

namespace Database\Seeders;

use App\Models\ProgramTab;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RemoveStageTab extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProgramTab::where('tab', 'stages')->delete();
    }
}
