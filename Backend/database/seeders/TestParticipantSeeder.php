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
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('Test@123'),
                'phone' => '0500000000',
                'current_role' => 'university_student',
                'is_active' => true,
            ]
        );

        $this->command->info('Test participant created: testuser@innovation-platform.com / Test@123');
    }
}
