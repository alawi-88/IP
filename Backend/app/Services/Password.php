<?php

namespace App\Services;

class Password
{
    public function forgot($participant): int
    {
        $code = rand(100000, 999999);

        $participant->update(['password_reset_code' => $code]);

        return $code;
    }

    public function reset($participant, $password): void
    {
        $participant->update([
            'password' => bcrypt($password),
            'password_reset_code' => null,
        ]);
    }
}
