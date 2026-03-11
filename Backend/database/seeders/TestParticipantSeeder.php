<?php

namespace Database\Seeders;

use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestParticipantSeeder extends Seeder
{
    public function run(): void
    {
        Participant::updateOrCreate(
            ['email' => 'testuser@innovation-platform.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('Test@123'),
                'phone' => '0500000000',
                'gender' => 'male',
                'date_of_birth' => '1995-01-01',
                'educational_background' => 'bachelor',
                'current_role' => 'university_student',
                'years_of_experience' => 'one_to_three',
                'is_active' => true,
            ]
        );

        $this->command->info('Test participant created: testuser@innovation-platform.com / Test@123');
    }
}
