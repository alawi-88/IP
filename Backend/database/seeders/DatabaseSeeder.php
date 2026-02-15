<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
//            ApprovalRequestPermissionSeeder::class,
//            ApprovalWorkflowSeeder::class,
//            EventArchivePermissionSeeder::class,
//            ArchivePermissionSeeder::class,
//            UserArchivePermissionSeeder::class,
//            ApplicationArchivePermissionSeeder::class,
//            ProjectArchivePermissionSeeder::class,
//            EvaluationArchivePermissionSeeder::class,
//            FormArchivePermissionSeeder::class,
//            ContactArchivePermissionSeeder::class,
//            ProjectFormConfigArchivePermissionSeeder::class,
        ]);
    }
}
