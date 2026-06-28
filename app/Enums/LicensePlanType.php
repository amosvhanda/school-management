<?php

namespace App\Enums;

enum LicensePlanType: string
{
    case Lifetime = 'lifetime';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Lifetime => 'Lifetime',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
            self::Custom => 'Custom',
        };
    }

    public function durationMonths(?int $customMonths = null): ?int
    {
        if ($this === self::Lifetime) {
            return null;
        }

        if ($this === self::Custom) {
            return $customMonths;
        }

        return config("license.plans.{$this->value}");
    }
}
