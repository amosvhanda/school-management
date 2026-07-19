<?php

namespace Tests\Unit;

use App\Rules\ZimbabweMobileNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ZimbabweMobileNumberTest extends TestCase
{
    #[Test]
    #[DataProvider('validNumbers')]
    public function it_accepts_valid_zimbabwe_mobiles(string $value): void
    {
        $this->assertTrue(ZimbabweMobileNumber::isValid($value), "Expected valid: {$value}");
    }

    #[Test]
    #[DataProvider('invalidNumbers')]
    public function it_rejects_invalid_numbers(string $value): void
    {
        $this->assertFalse(ZimbabweMobileNumber::isValid($value), "Expected invalid: {$value}");
    }

    public static function validNumbers(): array
    {
        return [
            'netone spaced intl' => ['+263 713 192 247'],
            'netone compact intl' => ['+263713192247'],
            'netone local' => ['0713192247'],
            'netone local spaced' => ['071 319 2247'],
            'econet 77' => ['077 123 4567'],
            'econet 78 intl' => ['+263 78 123 4567'],
            'telecel 73' => ['0731234567'],
            'intl with mistaken zero' => ['+263 0713 192 247'],
            '263 without plus' => ['263713192247'],
        ];
    }

    public static function invalidNumbers(): array
    {
        return [
            'landline' => ['0242123456'],
            'too short' => ['0713124'],
            'wrong prefix 72' => ['0721234567'],
            'letters' => ['0713ABC2247'],
            'foreign' => ['+27821234567'],
        ];
    }
}
