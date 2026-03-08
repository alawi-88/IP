<?php

namespace Database\Seeders;

use App\Models\Program;
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

        $programs = Program::all();

        foreach ($programs as $program) {
            $program->stages()->create([
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

            $program->stages()->create([
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

            $program->stages()->create([
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
