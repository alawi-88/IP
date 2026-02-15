<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Stage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('stages')->truncate();

        $competitions = Competition::all();

        foreach ($competitions as $competition) {
            $competition->stages()->create([
                'title' => [
                    'en' => 'Registration',
                    'ar' => 'التسجيل',
                ],
                'description' => [
                    'en' => 'Registration',
                    'ar' => 'التسجيل',
                ],
                'slug' => 'registration',
            ]);

            $competition->stages()->create([
                'title' => [
                    'en' => 'Team Formation',
                    'ar' => 'تكوين الفريق',
                ],
                'description' => [
                    'en' => 'Team Formation',
                    'ar' => 'تكوين الفريق',
                ],
                'slug' => 'team-formation',
            ]);

            $competition->stages()->create([
                'title' => [
                    'en' => 'Project Submission',
                    'ar' => 'تقديم المشروع',
                ],
                'description' => [
                    'en' => 'Project Submission',
                    'ar' => 'تقديم المشروع',
                ],
                'slug' => 'project-submission',
            ]);
        }
    }
}
