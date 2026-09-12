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

    public function test_phase2_full_chain_and_exceptions(): void
    {
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::SAMPLE_RECEIVED, OrderStatus::IN_ANALYSIS));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::IN_ANALYSIS, OrderStatus::PENDING_REVIEW));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::PENDING_REVIEW, OrderStatus::READY));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::READY, OrderStatus::COMPLETED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::COMPLETED, OrderStatus::ARCHIVED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::REQUESTED, OrderStatus::REJECTED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::REQUESTED, OrderStatus::DEFERRED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::REJECTED, OrderStatus::READY));
    }
}
