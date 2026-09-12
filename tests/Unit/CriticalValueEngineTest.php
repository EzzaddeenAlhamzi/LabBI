<?php

namespace Tests\Unit;

use App\Domain\Critical\CriticalValueEngine;
use App\Domain\Critical\ResultClassification;
use App\Models\TestReferenceRange;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Extensive Critical Value Engine matrix — SINGLE authoritative classifier.
 * Demo ranges are fixtures only (CAT-04 not signed off).
 */
class CriticalValueEngineTest extends TestCase
{
    private CriticalValueEngine $engine;

    private TestReferenceRange $range;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CriticalValueEngine;
        // HB-like demo fixture: normal 12–16, critical ≤7 / ≥20
        $this->range = new TestReferenceRange([
            'id' => 1,
            'normal_low' => 12,
            'normal_high' => 16,
            'critical_low' => 7,
            'critical_high' => 20,
            'is_active' => true,
        ]);
        $this->range->id = 1;
    }

    public function test_normal_midpoint(): void
    {
        $out = $this->engine->classify(14, $this->range, 'g/dL', 'g/dL');
        $this->assertSame(ResultClassification::NORMAL, $out->classification);
        $this->assertFalse($out->isCritical);
        $this->assertSame('within_normal', $out->reason);
    }

    public function test_below_normal_not_critical(): void
    {
        $out = $this->engine->classify(10, $this->range);
        $this->assertSame(ResultClassification::ABNORMAL, $out->classification);
        $this->assertFalse($out->isCritical);
    }

    public function test_above_normal_not_critical(): void
    {
        $out = $this->engine->classify(18, $this->range);
        $this->assertSame(ResultClassification::ABNORMAL, $out->classification);
        $this->assertFalse($out->isCritical);
    }

    public function test_critical_low(): void
    {
        $out = $this->engine->classify(5, $this->range);
        $this->assertSame(ResultClassification::CRITICAL, $out->classification);
        $this->assertTrue($out->isCritical);
        $this->assertSame('critical_low', $out->reason);
    }

    public function test_critical_high(): void
    {
        $out = $this->engine->classify(22, $this->range);
        $this->assertSame(ResultClassification::CRITICAL, $out->classification);
        $this->assertTrue($out->isCritical);
        $this->assertSame('critical_high', $out->reason);
    }

    #[DataProvider('boundaryProvider')]
    public function test_boundaries(float $value, string $expected): void
    {
        $out = $this->engine->classify($value, $this->range);
        $this->assertSame($expected, $out->classification, "value={$value}");
    }

    public static function boundaryProvider(): array
    {
        return [
            'critical_low_inclusive' => [7.0, ResultClassification::CRITICAL],
            'just_above_critical_low' => [7.0001, ResultClassification::ABNORMAL],
            'normal_low_inclusive' => [12.0, ResultClassification::NORMAL],
            'normal_high_inclusive' => [16.0, ResultClassification::NORMAL],
            'just_below_critical_high' => [19.9999, ResultClassification::ABNORMAL],
            'critical_high_inclusive' => [20.0, ResultClassification::CRITICAL],
        ];
    }

    public function test_missing_range(): void
    {
        $out = $this->engine->classify(14, null);
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
        $this->assertSame('missing_range', $out->reason);
    }

    public function test_inactive_range_treated_as_missing(): void
    {
        $inactive = new TestReferenceRange([
            'normal_low' => 12,
            'normal_high' => 16,
            'critical_low' => 7,
            'critical_high' => 20,
            'is_active' => false,
        ]);
        $out = $this->engine->classify(14, $inactive);
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
    }

    public function test_invalid_result_non_numeric(): void
    {
        $out = $this->engine->classify('not-a-number', $this->range);
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
        $this->assertSame('invalid_result_non_numeric', $out->reason);
    }

    public function test_missing_value(): void
    {
        $out = $this->engine->classify(null, $this->range);
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
        $this->assertSame('missing_value', $out->reason);
    }

    public function test_wrong_unit_incompatible(): void
    {
        $out = $this->engine->classify(14, $this->range, 'mmol/L', 'g/dL');
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
        $this->assertSame('incompatible_unit', $out->reason);
    }

    public function test_unit_normalization_whitespace_case(): void
    {
        $out = $this->engine->classify(14, $this->range, 'G/dL', 'g/dl');
        $this->assertSame(ResultClassification::NORMAL, $out->classification);
    }

    public function test_empty_range_bounds(): void
    {
        $empty = new TestReferenceRange([
            'normal_low' => null,
            'normal_high' => null,
            'critical_low' => null,
            'critical_high' => null,
            'is_active' => true,
        ]);
        $empty->id = 99;
        $out = $this->engine->classify(14, $empty);
        $this->assertSame(ResultClassification::UNKNOWN, $out->classification);
        $this->assertSame('missing_range', $out->reason);
    }

    public function test_threshold_snapshot_present_on_classify(): void
    {
        $out = $this->engine->classify(5, $this->range, 'g/dL', 'g/dL');
        $this->assertIsArray($out->thresholdSnapshot);
        $this->assertSame(7.0, $out->thresholdSnapshot['critical_low']);
        $this->assertStringContainsString('CAT-04', $out->thresholdSnapshot['note']);
    }
}
