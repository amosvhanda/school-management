<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicMediaUrl
{
    /** Turn a stored path or partial URL into a public disk URL. */
    public static function toPublicUrl(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (Str::startsWith($value, ['http://', 'https://', 'data:', 'blob:'])) {
            return $value;
        }

        if (Str::startsWith($value, '/storage/')) {
            return url($value);
        }

        $path = Str::startsWith($value, 'storage/')
            ? Str::after($value, 'storage/')
            : ltrim($value, '/');

        return Storage::disk('public')->url($path);
    }
}
