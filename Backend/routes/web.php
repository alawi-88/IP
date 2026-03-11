<?php

use App\Livewire\VerifyAdminOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Route::get('storage/filament_exports/{export}', \App\Http\Controllers\DownloadExport::class)
//    ->name('filament.exports.download')
//    ->middleware(['web', 'auth']);

Route::middleware(['web'])->group(function () {
    Route::get('/admin/verify-otp', VerifyAdminOtp::class)->name('admin.verify-otp');
});


// TEMPORARY: Setup route for venture module integration (remove after setup)
Route::get('/setup-ventures/{token}', function (Request $request, string $token) {
    if ($token !== 'integrate-startup-builder-2026') {
        abort(404);
    }

    $output = [];

    // Step 1: Run venture migrations
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output[] = 'Migrations: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        $output[] = 'Migration error: ' . $e->getMessage();
    }

    // Step 2: Seed admin user with known password
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
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::where('guard_name', 'web')->get());
        $output[] = 'Admin user created/updated: admin@innovation-platform.com / Admin@123';
    } catch (\Exception $e) {
        $output[] = 'Admin seed error: ' . $e->getMessage();
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
        $output[] = 'Test user created/updated: testuser@innovation-platform.com / Test@123';
    } catch (\Exception $e) {
        $output[] = 'Test user seed error: ' . $e->getMessage();
    }

    // Step 4: Run VentureDataSeeder
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\VentureDataSeeder',
            '--force' => true,
        ]);
        $output[] = 'VentureDataSeeder: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        $output[] = 'VentureDataSeeder error: ' . $e->getMessage();
    }

    // Step 5: Clear caches
    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        $output[] = 'Caches cleared successfully.';
    } catch (\Exception $e) {
        $output[] = 'Cache clear error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'completed',
        'steps' => $output,
    ], 200);
});

Route::post('/filament/competition-switch', function (Request $request) {
     session()->put(['current_competition_id' => $request->competition_id]);
    return redirect()->back();
})->name('filament.competition.switch')->middleware(['auth']);
