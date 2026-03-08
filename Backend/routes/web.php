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


Route::post('/filament/program-switch', function (Request $request) {
     session()->put(['current_program_id' => $request->program_id]);
    return redirect()->back();
})->name('filament.program.switch')->middleware(['auth']);
