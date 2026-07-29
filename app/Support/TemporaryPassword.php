<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TemporaryPassword
{
    /**
     * Generate a one-time password that meets Fortify/min-length rules.
     */
    public static function generate(int $length = 14): string
    {
        return Str::password($length);
    }
}
