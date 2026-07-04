<?php

namespace Database\Seeders\Helpers;

final class ZimbabweData
{
    public const FIRST_NAMES = [
        'Tinashe', 'Tafadzwa', 'Nyasha', 'Ruvimbo', 'Chipo', 'Rudo', 'Tapiwa', 'Farai',
        'Tatenda', 'Chenai', 'Kuda', 'Tawanda', 'Tendai', 'Rumbidzai', 'Miriam', 'Blessing',
    ];

    public const SURNAMES = [
        'Moyo', 'Nyamande', 'Chiremba', 'Chigova', 'Gondo', 'Nkomo', 'Chiwara', 'Muzenda',
        'Chikomo', 'Mutasa', 'Makombe', 'Mhlanga', 'Dube', 'Ncube', 'Sibanda', 'Ndlovu',
    ];

    public const SCHOOLS = [
        ['name' => 'Mufakose 1 High School', 'code' => 'MUF001'],
        ['name' => 'Allan Wilson Boys High', 'code' => 'AWBH01'],
        ['name' => 'Prince Edward School', 'code' => 'PRED01'],
        ['name' => 'St Johns College', 'code' => 'STJH01'],
        ['name' => 'Harare High School', 'code' => 'HARH01'],
    ];

    public const CLASS_NAMES = ['Grade 7', 'Form 1A', 'Form 2B', 'Form 3B', 'Form 4A', 'Lower 6', 'Upper 6'];

    public const SUBJECTS = ['Mathematics', 'English', 'Shona', 'Science', 'History', 'Geography'];

    public const FEE_CATEGORIES = ['Tuition Fees', 'Development Levy', 'Examination Fees', 'Sports Levy', 'Library Fees', 'Lab Fees'];

    public const PAYMENT_METHODS = ['cash', 'ecocash', 'onemoney', 'zipit', 'swipe', 'bank_transfer'];

    public const CURRENCIES = ['USD', 'ZWL'];

    public static function phone(): string
    {
        $prefixes = ['0772', '0782', '0712', '0773', '0784', '0713', '0774'];
        $prefix = $prefixes[array_rand($prefixes)];
        return "+263 {$prefix} " . rand(100, 999) . ' ' . rand(100, 999);
    }

    public static function firstName(): string
    {
        return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
    }

    public static function surname(): string
    {
        return self::SURNAMES[array_rand(self::SURNAMES)];
    }

    public static function fullName(): string
    {
        return self::firstName() . ' ' . self::surname();
    }

    /**
     * Map a class display name (e.g. Form 3B) to the canonical grade level name.
     */
    public static function gradeLevelNameForClass(string $className): string
    {
        $normalized = trim($className);

        if ($normalized === 'Grade 7') {
            return 'Grade 7';
        }

        if (preg_match('/^Form 1/i', $normalized)) {
            return 'Form 1';
        }
        if (preg_match('/^Form 2/i', $normalized)) {
            return 'Form 2';
        }
        if (preg_match('/^Form 3/i', $normalized)) {
            return 'Form 3';
        }
        if (preg_match('/^Form 4/i', $normalized)) {
            return 'Form 4';
        }
        if (stripos($normalized, 'Lower 6') === 0) {
            return 'Lower 6';
        }
        if (stripos($normalized, 'Upper 6') === 0) {
            return 'Upper 6';
        }

        return preg_replace('/\s*[AB]?\s*$/', '', $normalized) ?: $normalized;
    }
}
