<?php

namespace App\Listeners;

use App\Models\AdminOtpToken;
use App\Notifications\AdminOtpMail;
use Illuminate\Auth\Events\Login;

class SendAdminOtp
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Don't send OTP if user is already authenticated
        if (auth()->check()) {
            return;
        }

        if (session('admin.otp_verified') === true) {
            return;
        }

        // Check if there's already a recent OTP (within 1 minute) to prevent duplicate sending
        $existingOtp = AdminOtpToken::where('user_id', $user->id)
            ->where('last_sent_at', '>', now()->subMinutes(1))
            ->latest()
            ->first();

        if ($existingOtp) {
            return;
        }

        // Delete any old OTP tokens for this user to prevent reuse
        AdminOtpToken::where('user_id', $user->id)->delete();

        $otp = rand(100000, 999999);

        AdminOtpToken::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        $user->notify(new AdminOtpMail($otp));

        session(['pending_user_id' => $user->id]);
    }

}
