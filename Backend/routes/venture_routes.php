<?php

use App\Http\Controllers\Api\Participant\VentureController;
use Illuminate\Support\Facades\Route;

/**
 * Venture Routes
 * 
 * These routes should be placed inside the participant authenticated middleware group in api.php.
 * Add this line to api.php (inside the middleware group):
 * 
 * require __DIR__ . '/venture_routes.php';
 * 
 * Example api.php structure:
 * 
 * Route::middleware(['auth:sanctum', 'participant'])->group(function () {
 *     require __DIR__ . '/venture_routes.php';
 * });
 */

Route::prefix('ventures')->name('ventures.')->group(function () {
    // List all ventures for the authenticated participant
    Route::get('/', [VentureController::class, 'index'])
        ->name('index');

    // Create a new venture
    Route::post('/', [VentureController::class, 'store'])
        ->name('store');

    // Get a specific venture with tabs and sections
    Route::get('{venture}', [VentureController::class, 'show'])
        ->name('show');

    // Get generation progress for a venture
    Route::get('{venture}/progress', [VentureController::class, 'progress'])
        ->name('progress');

    // Retry all failed sections for a venture
    Route::post('{venture}/retry-failed', [VentureController::class, 'retryFailed'])
        ->name('retry-failed');

    // Regenerate a specific section
    Route::post('{venture}/sections/{section}/regenerate', [VentureController::class, 'regenerateSection'])
        ->name('sections.regenerate');

    // Update a venture section's content
    Route::put('{venture}/sections/{section}', [VentureController::class, 'updateSection'])
        ->name('sections.update');

    // Toggle archive status of a venture
    Route::post('{venture}/toggle-archive', [VentureController::class, 'toggleArchive'])
        ->name('toggle-archive');
});
