<?php

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class RedirectToOtpAfterLogin implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect()->route('admin.verify-otp');
    }
}
