<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Zimbabwe mobile: 07X XXX XXXX or +263 7X XXX XXXX
 * Operators: 71 NetOne, 73 Telecel, 77/78 Econet.
 */
class ZimbabweMobileNumber implements ValidationRule
{
    public const HINT = 'e.g. 071 123 4567 or +263 71 123 4567';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! self::isValid($value)) {
            $fail('Enter a valid Zimbabwe mobile number ('.self::HINT.').');
        }
    }

    public static function isValid(string $value): bool
    {
        $normalized = preg_replace('/[\s\-().]/', '', $value) ?? '';
        // Common mistake: +263 0713… → +263 713…
        $normalized = preg_replace('/^(\+?263)0/', '$1', $normalized) ?? $normalized;

        return (bool) preg_match('/^(\+263|263|0)?7[1378]\d{7}$/', $normalized);
    }

    /** @return list<string|self> */
    public static function required(): array
    {
        return ['required', 'string', 'max:30', new self];
    }

    /** @return list<string|self> */
    public static function optional(): array
    {
        return ['nullable', 'string', 'max:30', new self];
    }

    /** @return list<string|self> */
    public static function sometimes(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:30', new self];
    }
}
