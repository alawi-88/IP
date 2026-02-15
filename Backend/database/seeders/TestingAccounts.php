<?php

namespace Database\Seeders;

use App\Models\CompetitionApplication;
use App\Models\Participant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestingAccounts extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            'ff50@ff.com',
            'ff51@ff.com',
            'ff52@ff.com',
            'ff53@ff.com',
            'ff54@ff.com',
            'ff55@ff.com',
            'ff56@ff.com',
            'ff57@ff.com',
            'ff58@ff.com',
            'ff59@ff.com',
            'ff60@ff.com',
        ];

        foreach ($emails as $key => $email) {
            $participant = Participant::updateOrCreate(['email' => $email,], [
                    'name' => 'John Doe ' . $key,
                    'phone' => '1234' . rand(10000, 99999),
                    'gender' => 'male',
                    'date_of_birth' => '1992-01-01',
                    'nationality' => 'US',
                    'country' => 'US',
                    'residence_city' => 'New York',
                    'password' => '123456789aA*',
                    'educational_background' => 'bachelor',
                    'current_role' => 'private_sector_employee',
                    'place_of_work_study' => 'Company X',
                    'years_of_experience' => 'one_to_three',
                    'experience_or_skills' => 'Software Development',
                    'key_achievements' => 'Developed a CRM system',
                ]
            );

            CompetitionApplication::updateOrCreate(['participant_id' => $participant->id, 'competition_id' => 1],
                [
                    'has_team' => false,
                    'has_idea' => false,
                    'participation_interest' => 'interest description'
                ]);

            CompetitionApplication::updateOrCreate(['participant_id' => $participant->id, 'competition_id' => 2],
                [
                    'has_team' => false,
                    'has_idea' => false,
                    'participation_interest' => 'interest description'
                ]);
        }
    }
}
