<?php

namespace App\Domain\Bi;

/**
 * Pure trend math — deterministic and NON-medical.
 *
 * Formula (documented):
 * 1. Require ≥ 2 chronological numeric points with compatible units.
 * 2. Units are compatible iff normalize(unitA) === normalize(unitB)
 *    where normalize = trim + lowercase. Empty units are compatible with empty only.
 * 3. Let first = earliest value, last = latest value.
 * 4. baseline = max(|first|, |last|, 1e-9)
 * 5. relative = |last - first| / baseline
 * 6. If relative < STABLE_RELATIVE_THRESHOLD (0.05) → Stable
 *    Else if last > first → Rising
 *    Else if last < first → Falling
 *    Else → Stable
 *
 * Does NOT diagnose disease or interpret clinical significance.
 */
final class TrendCalculator
{
    /**
     * @param  list<array{value: mixed, unit: ?string}>  $points  chronological oldest→newest
     * @return array{trend: string, label_ar: string, color: string, limitation: ?string, first: ?float, last: ?float, relative_change: ?float}
     */
    public function calculate(array $points): array
    {
        if (count($points) < 2) {
            return $this->outcome(BiTrend::INSUFFICIENT_DATA, 'يلزم نقطتان معتمدتان على الأقل.');
        }

        $numeric = [];
        foreach ($points as $point) {
            $value = $point['value'] ?? null;
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return $this->outcome(BiTrend::NON_NUMERIC, 'لا يمكن حساب الاتجاه لقيم غير رقمية.');
            }
            $numeric[] = [
                'value' => (float) $value,
                'unit' => $this->normalizeUnit($point['unit'] ?? null),
            ];
        }

        $units = array_unique(array_column($numeric, 'unit'));
        if (count($units) > 1) {
            return $this->outcome(BiTrend::INCOMPATIBLE_UNITS, 'وحدات القياس غير متوافقة — لم يُحسب الاتجاه.');
        }

        $first = $numeric[0]['value'];
        $last = $numeric[array_key_last($numeric)]['value'];
        $baseline = max(abs($first), abs($last), 1e-9);
        $relative = abs($last - $first) / $baseline;

        if ($relative < BiTrend::STABLE_RELATIVE_THRESHOLD) {
            $trend = BiTrend::STABLE;
        } elseif ($last > $first) {
            $trend = BiTrend::RISING;
        } elseif ($last < $first) {
            $trend = BiTrend::FALLING;
        } else {
            $trend = BiTrend::STABLE;
        }

        return [
            'trend' => $trend,
            'label_ar' => BiTrend::labelAr($trend),
            'color' => BiTrend::color($trend),
            'limitation' => null,
            'first' => $first,
            'last' => $last,
            'relative_change' => round($relative, 6),
        ];
    }

    public function unitsCompatible(?string $a, ?string $b): bool
    {
        return $this->normalizeUnit($a) === $this->normalizeUnit($b);
    }

    public function normalizeUnit(?string $unit): string
    {
        return strtolower(trim((string) $unit));
    }

    /** @return array{trend: string, label_ar: string, color: string, limitation: ?string, first: ?float, last: ?float, relative_change: ?float} */
    private function outcome(string $trend, string $limitation): array
    {
        return [
            'trend' => $trend,
            'label_ar' => BiTrend::labelAr($trend),
            'color' => BiTrend::color($trend),
            'limitation' => $limitation,
            'first' => null,
            'last' => null,
            'relative_change' => null,
        ];
    }
}
