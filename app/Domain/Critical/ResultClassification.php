<?php

namespace App\Domain\Critical;

/**
 * Classification labels produced ONLY by CriticalValueEngine.
 * UI must display these; never recompute thresholds in JavaScript.
 */
final class ResultClassification
{
    public const NORMAL = 'normal';

    public const ABNORMAL = 'abnormal';

    public const CRITICAL = 'critical';

    public const UNKNOWN = 'unknown';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::NORMAL, self::ABNORMAL, self::CRITICAL, self::UNKNOWN];
    }

    public static function labelAr(string $code): string
    {
        return match ($code) {
            self::NORMAL => 'طبيعي',
            self::ABNORMAL => 'غير طبيعي',
            self::CRITICAL => 'حرج',
            default => 'غير مصنّف',
        };
    }
}
