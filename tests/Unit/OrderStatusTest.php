<?php

namespace Tests\Unit;

use App\Domain\Workflow\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_phase1_transition_to_sample_received(): void
    {
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::REQUESTED, OrderStatus::SAMPLE_RECEIVED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::REQUESTED, OrderStatus::READY));
    }
}
