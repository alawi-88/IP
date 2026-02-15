<?php

namespace Database\Seeders;

use App\Models\Judge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JudgeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Judge::create([
            'name' => [
                'en' => 'Mohamed Gamal',
                'ar' => 'محمد جمال',
            ],
            'email' => 'test-judge@filmathon.com',
            'phone_number' => '01000000000',
            'experience_field' => [
                'en' => 'Software Engineering',
                'ar' => 'هندسة البرمجيات',
            ],
            'password' => bcrypt('123456789aA*'),
        ]);
    }
}
