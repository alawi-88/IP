<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use App\Notifications\CustomResetPasswordNotification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

class CustomRequestPasswordReset extends BaseRequestPasswordReset
{
    protected function getPasswordResetNotification(string $token): \Illuminate\Notifications\Notification
    {
        return new CustomResetPasswordNotification($token);
    }
}
