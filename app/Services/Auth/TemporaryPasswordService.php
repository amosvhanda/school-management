<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;

class TemporaryPasswordService
{
    /**
     * Cryptographically random temporary password (never a fixed default).
     * Callers should force a reset on first login when possible.
     */
    public function generate(int $length = 20): string
    {
        // Mix of letters, digits, and symbols without ambiguous characters.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        $password = '';
        $max = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        // Ensure at least one letter and one digit for password policy compatibility.
        if (! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
            return $this->generate($length);
        }

        return $password;
    }

    public function hashNew(): array
    {
        $plain = $this->generate();

        return [
            'plain' => $plain,
            'hash' => bcrypt($plain),
        ];
    }
}
