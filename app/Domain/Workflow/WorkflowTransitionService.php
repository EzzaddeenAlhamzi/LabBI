<?php

namespace App\Domain\Workflow;

use App\Models\TestOrder;
use DomainException;

/**
 * Backend-enforced order workflow transitions (WFL-01).
 * Controllers must call this — never rely on frontend-only enforcement.
 */
final class WorkflowTransitionService
{
    public function transition(TestOrder $order, string $to, ?string $reason = null): TestOrder
    {
        $from = $order->status;

        if (! OrderStatus::canTransition($from, $to)) {
            throw new DomainException("Invalid workflow transition: {$from} → {$to}");
        }

        $order->status = $to;
        if ($to === OrderStatus::ARCHIVED) {
            $order->archived_at = $order->archived_at ?? now();
        }
        if ($reason !== null && $reason !== '') {
            $order->notes = trim(($order->notes ? $order->notes."\n" : '').$reason);
        }
        $order->save();

        return $order->refresh();
    }

    public function assertCanTransition(TestOrder $order, string $to): void
    {
        if (! OrderStatus::canTransition($order->status, $to)) {
            throw new DomainException("Invalid workflow transition: {$order->status} → {$to}");
        }
    }
}
