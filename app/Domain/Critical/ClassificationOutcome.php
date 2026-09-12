<?php

namespace App\Domain\Critical;

/**
 * Immutable output of CriticalValueEngine (single authoritative classifier).
 */
final class ClassificationOutcome
{
    public function __construct(
        public readonly string $classification,
        public readonly bool $isCritical,
        public readonly ?float $normalLow,
        public readonly ?float $normalHigh,
        public readonly ?float $criticalLow,
        public readonly ?float $criticalHigh,
        public readonly ?int $referenceRangeId,
        public readonly string $reason,
        public readonly ?array $thresholdSnapshot = null,
    ) {}

    public function toArray(): array
    {
        return [
            'classification' => $this->classification,
            'is_critical' => $this->isCritical,
            'normal_low' => $this->normalLow,
            'normal_high' => $this->normalHigh,
            'critical_low' => $this->criticalLow,
            'critical_high' => $this->criticalHigh,
            'reference_range_id' => $this->referenceRangeId,
            'reason' => $this->reason,
            'threshold_snapshot' => $this->thresholdSnapshot,
        ];
    }
}
