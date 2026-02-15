<?php

namespace Database\Seeders;

use App\Models\NafathSettings;
use Illuminate\Database\Seeder;

class NafathSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default Nafath settings if they don't exist
        NafathSettings::firstOrCreate(
            ['id' => 1], // Use a specific ID to ensure only one record
            [
                'is_enabled' => false,
                'client_id' => null,
                'client_secret' => null,
                'redirect_uri' => null,
                'logout_uri' => null,
                'environment' => 'production',
            ]
        );

        $this->command->info('Nafath settings created successfully.');
    }
}
