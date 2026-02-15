<?php

namespace App\Filament\Pages\Auth;



use Filament\Pages\Auth\Login as FilamentLogin;
use Illuminate\Auth\Events\Login;
use App\Listeners\SendAdminOtp;
use RedirectToOtpAfterLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class CustomLogin extends FilamentLogin
{
    public function mount(): void
    {
        $this->form->fill([
            'email' => session('login_email', ''),
        ]);
    }

    public function authenticate(): LoginResponseContract
    {
        parent::authenticate();

        $user = auth()->user();

        if ($user) {
            // Check if user is archived
            if ($user->isArchived()) {
                auth()->logout();
                $this->addError('email', __('user_archive.account_archived'));
                return app(LoginResponseContract::class);
            }

            session(['login_email' => $user->email]);
            auth()->logout();
            session(['pending_user_id' => $user->id]);
            
            // Clear any existing OTP sessions and tokens
            session()->forget('admin.otp_verified');
            
            // Delete any existing OTP tokens for this user to prevent reuse
            \App\Models\AdminOtpToken::where('user_id', $user->id)->delete();

            // Only send OTP if user is not already authenticated
            if (!auth()->check()) {
                (new SendAdminOtp())->handle(
                    new Login('web', $user, false)
                );
            }

            return app(RedirectToOtpAfterLogin::class);
        }

        return app(LoginResponseContract::class);
    }

}


