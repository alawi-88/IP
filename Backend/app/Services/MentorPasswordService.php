<?php

namespace App\Services;

use App\Models\Mentor;

class MentorPasswordService
{
    public function forgot(Mentor $mentor): int
    {
        $code = rand(100000, 999999);

        // Set expiration to 1 minute from now
        $mentor->update([
            'password_reset_code' => $code,
            'password_reset_code_expires_at' => now()->addMinute(),
        ]);

        return $code;
    }

    public function reset(Mentor $mentor, string $password): void
    {
        $mentor->update([
            'password' => bcrypt($password),
            'password_reset_code' => null,
            'password_reset_code_expires_at' => null,
        ]);
    }
}
