<?php

namespace App\Livewire;

use App\Models\AdminOtpToken;
use App\Models\User;
use App\Notifications\AdminOtpMail;
use Livewire\Component;


class VerifyAdminOtp extends Component
{
    public string $otp = '';
    public string $notifyMessage = '';

    public function submit()
    {
        $pendingUserId = session('pending_user_id');

        if (!$pendingUserId) {
            return redirect()->route('filament.admin.auth.login');
        }

        $token = AdminOtpToken::where('user_id', $pendingUserId)
            ->where('otp', $this->otp)
            ->latest()
            ->first();

        if (!$token) {
            session()->flash('error', 'Incorrect OTP');
            return redirect()->route('admin.verify-otp');
        }

        if ($token->expires_at && $token->expires_at->lt(now())) {
            session()->flash('error', 'OTP has expired.');
            return redirect()->route('admin.verify-otp');
        }

        // Delete the used OTP token to prevent reuse
        $token->delete();

        auth()->loginUsingId($pendingUserId);
        session()->forget('pending_user_id');
        session()->put('admin.otp_verified', true);
        
        // Clear any remaining OTP tokens to prevent further OTP sending
        AdminOtpToken::where('user_id', $pendingUserId)->delete();

        return redirect()->route('filament.admin.pages.dashboard');
    }



    public function resendOtp()
    {
        $pendingUserId = session('pending_user_id');

        if (!$pendingUserId) {
            return redirect()->route('filament.admin.auth.login');
        }

        AdminOtpToken::where('user_id', $pendingUserId)->delete();

        $newOtp = rand(100000, 999999);

        AdminOtpToken::create([
            'user_id' => $pendingUserId,
            'otp' => $newOtp,
            'expires_at' => now()->addMinutes(5),
        ]);

        $user = User::find($pendingUserId);

        if ($user) {
            $user->notify(new AdminOtpMail($newOtp));
        }
        session()->flash('success', 'New OTP sent! Please check your email.');

        return redirect()->route('admin.verify-otp');
    }


    public function render()
    {
        $pendingUserId = session('pending_user_id');


        $timeLeft = 0;
        if ($pendingUserId) {
            $token = AdminOtpToken::where('user_id', $pendingUserId)
                ->latest()
                ->first();

            if ($token && $token->expires_at) {
                $timeLeft = max(0, (int) now()->diffInSeconds($token->expires_at, false));
            }
        }


        return view('livewire.verify-admin-otp', [
            'timeLeft' => $timeLeft
        ])->layout('layouts.filament-standalone');
    }

}

