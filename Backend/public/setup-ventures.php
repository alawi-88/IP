<?php
/**
 * TEMPORARY setup script for Startup Builder module integration.
 * DELETE THIS FILE after setup is complete.
 */

// Security token check
if (($_GET['token'] ?? '') !== 'integrate-startup-builder-2026') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = \Illuminate\Http\Request::capture());

$output = [];

// Step 0: Clear route cache first
try {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    $output[] = 'Step 0 - Caches cleared: ' . trim(\Illuminate\Support\Facades\Artisan::output());
} catch (\Exception $e) {
    $output[] = 'Step 0 - Cache clear error: ' . $e->getMessage();
}

// Step 1: Run venture migrations
try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrationOutput = trim(\Illuminate\Support\Facades\Artisan::output());
    $output[] = 'Step 1 - Migrations: ' . $migrationOutput;
} catch (\Exception $e) {
    $output[] = 'Step 1 - Migration error: ' . $e->getMessage();
}

// Step 2: Create/reset admin user
try {
    $role = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);

    $admin = \App\Models\User::updateOrCreate(
        ['email' => 'admin@innovation-platform.com'],
        [
            'name' => 'Platform Admin',
            'password' => bcrypt('Admin@123'),
            'is_archived' => false,
        ]
    );
    $admin->assignRole($role);

    // Give all permissions
    $permissions = \Spatie\Permission\Models\Permission::where('guard_name', 'web')->get();
    if ($permissions->count() > 0) {
        $admin->givePermissionTo($permissions);
    }
    $output[] = 'Step 2 - Admin created: admin@innovation-platform.com / Admin@123 (role: super-admin, permissions: ' . $permissions->count() . ')';
} catch (\Exception $e) {
    $output[] = 'Step 2 - Admin error: ' . $e->getMessage();
}

// Step 3: Create test participant user
try {
    $participantRole = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'participant',
        'guard_name' => 'web',
    ]);

    $participant = \App\Models\User::updateOrCreate(
        ['email' => 'testuser@innovation-platform.com'],
        [
            'name' => 'Test User',
            'password' => bcrypt('Test@123'),
            'is_archived' => false,
        ]
    );
    $participant->assignRole($participantRole);
    $output[] = 'Step 3 - Test user created: testuser@innovation-platform.com / Test@123 (role: participant)';
} catch (\Exception $e) {
    $output[] = 'Step 3 - Test user error: ' . $e->getMessage();
}

// Step 4: Run VentureDataSeeder
try {
    \Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\VentureDataSeeder',
        '--force' => true,
    ]);
    $seederOutput = trim(\Illuminate\Support\Facades\Artisan::output());
    $output[] = 'Step 4 - VentureDataSeeder: ' . $seederOutput;
} catch (\Exception $e) {
    $output[] = 'Step 4 - VentureDataSeeder error: ' . $e->getMessage();
}

// Step 5: Final cache clear
try {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    $output[] = 'Step 5 - Final optimize:clear done';
} catch (\Exception $e) {
    $output[] = 'Step 5 - Final clear error: ' . $e->getMessage();
}

// Output results
header('Content-Type: application/json');
echo json_encode([
    'status' => 'completed',
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => $output,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
