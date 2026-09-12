<?php

namespace App\Domain\Bi;

/**
 * Deterministic, NON-medical trend labels for historical comparison.
 * Never used for diagnosis, treatment advice, or disease prediction.
 */
final class BiTrend
{
    public const RISING = 'rising';

    public const FALLING = 'falling';

    public const STABLE = 'stable';

    public const INCOMPATIBLE_UNITS = 'incompatible_units';

    public const INSUFFICIENT_DATA = 'insufficient_data';

    public const NON_NUMERIC = 'non_numeric';

    /** Relative change below this threshold is treated as Stable (5%). */
    public const STABLE_RELATIVE_THRESHOLD = 0.05;

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::RISING,
            self::FALLING,
            self::STABLE,
            self::INCOMPATIBLE_UNITS,
            self::INSUFFICIENT_DATA,
            self::NON_NUMERIC,
        ];
    }

    public static function labelAr(string $code): string
    {
        return match ($code) {
            self::RISING => 'تصاعدي',
            self::FALLING => 'تنازلي',
            self::STABLE => 'مستقر',
            self::INCOMPATIBLE_UNITS => 'وحدات غير متوافقة',
            self::INSUFFICIENT_DATA => 'بيانات غير كافية',
            self::NON_NUMERIC => 'قيم غير رقمية',
            default => $code,
        };
    }

    public static function color(string $code): string
    {
        return match ($code) {
            self::RISING => '#9a6700',
            self::FALLING => '#0b5cab',
            self::STABLE => '#0f6b4c',
            default => '#4d6358',
        };
    }
}
