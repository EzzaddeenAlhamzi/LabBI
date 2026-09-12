<?php

namespace Tests\Unit;

use App\Domain\Bi\BiTrend;
use App\Domain\Bi\TrendCalculator;
use PHPUnit\Framework\TestCase;

class TrendCalculatorTest extends TestCase
{
    private TrendCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new TrendCalculator;
    }

    public function test_rising_when_last_higher(): void
    {
        $out = $this->calc->calculate([
            ['value' => 10, 'unit' => 'g/dL'],
            ['value' => 14, 'unit' => 'g/dL'],
        ]);
        $this->assertSame(BiTrend::RISING, $out['trend']);
        $this->assertNull($out['limitation']);
    }

    public function test_falling_when_last_lower(): void
    {
        $out = $this->calc->calculate([
            ['value' => 14, 'unit' => 'g/dL'],
            ['value' => 10, 'unit' => 'g/dL'],
        ]);
        $this->assertSame(BiTrend::FALLING, $out['trend']);
    }

    public function test_stable_within_five_percent(): void
    {
        $out = $this->calc->calculate([
            ['value' => 100, 'unit' => 'mg/dL'],
            ['value' => 104, 'unit' => 'mg/dL'],
        ]);
        $this->assertSame(BiTrend::STABLE, $out['trend']);
    }

    public function test_incompatible_units_safe(): void
    {
        $out = $this->calc->calculate([
            ['value' => 10, 'unit' => 'g/dL'],
            ['value' => 12, 'unit' => 'mmol/L'],
        ]);
        $this->assertSame(BiTrend::INCOMPATIBLE_UNITS, $out['trend']);
        $this->assertNotNull($out['limitation']);
    }

    public function test_unit_normalization_case_and_space(): void
    {
        $out = $this->calc->calculate([
            ['value' => 10, 'unit' => ' g/dL '],
            ['value' => 12, 'unit' => 'G/DL'],
        ]);
        $this->assertSame(BiTrend::RISING, $out['trend']);
    }

    public function test_insufficient_and_non_numeric(): void
    {
        $this->assertSame(BiTrend::INSUFFICIENT_DATA, $this->calc->calculate([['value' => 1, 'unit' => 'u']])['trend']);
        $this->assertSame(BiTrend::NON_NUMERIC, $this->calc->calculate([
            ['value' => 'abc', 'unit' => 'u'],
            ['value' => 2, 'unit' => 'u'],
        ])['trend']);
    }
}
