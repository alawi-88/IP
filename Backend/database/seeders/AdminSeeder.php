<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {

        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $user = User::updateOrCreate(
            ['email' => 'admin@innovation-platform.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );


        $user->assignRole($role);

        $user->givePermissionTo(Permission::where('guard_name', 'web')->get());
    }
}
