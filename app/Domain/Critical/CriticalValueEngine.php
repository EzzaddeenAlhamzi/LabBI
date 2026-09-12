<?php

namespace App\Domain\Critical;

use App\Models\LabTest;
use App\Models\TestReferenceRange;
use InvalidArgumentException;

/**
 * SINGLE authoritative Critical Value Engine for LabBI.
 *
 * - Loads reference + critical ranges from persisted catalog data
 * - Compares numeric results and classifies Normal / Abnormal / Critical
 * - Does NOT invent medical ranges; uses test_reference_ranges only
 * - Demo catalog values are TEST-ONLY until CAT-04 medical sign-off
 * - No JavaScript/client duplicate of this logic is allowed
 */
final class CriticalValueEngine
{
    /**
     * Classify a numeric result against the active range for a test.
     *
     * Boundary rules (inclusive):
     * - Critical if value <= critical_low OR value >= critical_high (when bound present)
     * - Else Normal if normal_low <= value <= normal_high (when both bounds present)
     * - Else Abnormal if outside normal but not critical
     * - Unknown if missing usable range or invalid input
     */
    public function classify(
        float|int|string|null $value,
        ?TestReferenceRange $range,
        ?string $resultUnit = null,
        ?string $expectedUnit = null,
    ): ClassificationOutcome {
        if ($value === null || $value === '') {
            return $this->unknown('missing_value');
        }

        if (! is_numeric($value)) {
            return $this->unknown('invalid_result_non_numeric');
        }

        $numeric = (float) $value;
        if (! is_finite($numeric)) {
            return $this->unknown('invalid_result_non_finite');
        }

        if ($this->unitsIncompatible($resultUnit, $expectedUnit)) {
            return $this->unknown('incompatible_unit', $range);
        }

        if ($range === null || ! $range->is_active) {
            return $this->unknown('missing_range');
        }

        $normalLow = $this->nullableFloat($range->normal_low);
        $normalHigh = $this->nullableFloat($range->normal_high);
        $criticalLow = $this->nullableFloat($range->critical_low);
        $criticalHigh = $this->nullableFloat($range->critical_high);

        if ($normalLow === null && $normalHigh === null && $criticalLow === null && $criticalHigh === null) {
            return $this->unknown('missing_range', $range);
        }

        $snapshot = [
            'reference_range_id' => $range->id,
            'normal_low' => $normalLow,
            'normal_high' => $normalHigh,
            'critical_low' => $criticalLow,
            'critical_high' => $criticalHigh,
            'unit' => $expectedUnit,
            'note' => 'Demo/catalog ranges are test-only until CAT-04 medical sign-off',
        ];

        if ($criticalLow !== null && $numeric <= $criticalLow) {
            return new ClassificationOutcome(
                ResultClassification::CRITICAL,
                true,
                $normalLow,
                $normalHigh,
                $criticalLow,
                $criticalHigh,
                $range->id,
                'critical_low',
                $snapshot,
            );
        }

        if ($criticalHigh !== null && $numeric >= $criticalHigh) {
            return new ClassificationOutcome(
                ResultClassification::CRITICAL,
                true,
                $normalLow,
                $normalHigh,
                $criticalLow,
                $criticalHigh,
                $range->id,
                'critical_high',
                $snapshot,
            );
        }

        $hasNormalBand = $normalLow !== null && $normalHigh !== null;
        if ($hasNormalBand) {
            if ($numeric >= $normalLow && $numeric <= $normalHigh) {
                return new ClassificationOutcome(
                    ResultClassification::NORMAL,
                    false,
                    $normalLow,
                    $normalHigh,
                    $criticalLow,
                    $criticalHigh,
                    $range->id,
                    'within_normal',
                    $snapshot,
                );
            }

            return new ClassificationOutcome(
                ResultClassification::ABNORMAL,
                false,
                $normalLow,
                $normalHigh,
                $criticalLow,
                $criticalHigh,
                $range->id,
                'outside_normal',
                $snapshot,
            );
        }

        // Partial normal bounds: treat outside known bound as abnormal when not critical
        if ($normalLow !== null && $numeric < $normalLow) {
            return new ClassificationOutcome(
                ResultClassification::ABNORMAL,
                false,
                $normalLow,
                $normalHigh,
                $criticalLow,
                $criticalHigh,
                $range->id,
                'below_normal_low',
                $snapshot,
            );
        }

        if ($normalHigh !== null && $numeric > $normalHigh) {
            return new ClassificationOutcome(
                ResultClassification::ABNORMAL,
                false,
                $normalLow,
                $normalHigh,
                $criticalLow,
                $criticalHigh,
                $range->id,
                'above_normal_high',
                $snapshot,
            );
        }

        return new ClassificationOutcome(
            ResultClassification::UNKNOWN,
            false,
            $normalLow,
            $normalHigh,
            $criticalLow,
            $criticalHigh,
            $range->id,
            'incomplete_normal_band',
            $snapshot,
        );
    }

    /**
     * Load the active range for a catalog test and classify.
     */
    public function classifyForTest(LabTest $test, float|int|string|null $value, ?string $resultUnit = null): ClassificationOutcome
    {
        $range = $test->referenceRanges()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        return $this->classify($value, $range, $resultUnit, $test->unit);
    }

    private function unknown(string $reason, ?TestReferenceRange $range = null): ClassificationOutcome
    {
        return new ClassificationOutcome(
            ResultClassification::UNKNOWN,
            false,
            $range ? $this->nullableFloat($range->normal_low) : null,
            $range ? $this->nullableFloat($range->normal_high) : null,
            $range ? $this->nullableFloat($range->critical_low) : null,
            $range ? $this->nullableFloat($range->critical_high) : null,
            $range?->id,
            $reason,
            $range ? [
                'reference_range_id' => $range->id,
                'reason' => $reason,
            ] : null,
        );
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Range bound must be numeric or null');
        }

        return (float) $value;
    }

    private function unitsIncompatible(?string $resultUnit, ?string $expectedUnit): bool
    {
        if ($resultUnit === null || $resultUnit === '' || $expectedUnit === null || $expectedUnit === '') {
            return false;
        }

        return $this->normalizeUnit($resultUnit) !== $this->normalizeUnit($expectedUnit);
    }

    private function normalizeUnit(string $unit): string
    {
        return strtolower(preg_replace('/\s+/', '', $unit) ?? $unit);
    }
}
