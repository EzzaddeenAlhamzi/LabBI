<?php

namespace App\Domain\Workflow;

/**
 * Unified order status machine (D-005 / WFL-01).
 * Phase 1 uses requested → sample_received; later transitions reserved for P2+.
 */
final class OrderStatus
{
    public const REQUESTED = 'requested';

    public const SAMPLE_RECEIVED = 'sample_received';

    public const IN_ANALYSIS = 'in_analysis';

    public const PENDING_REVIEW = 'pending_review';

    public const READY = 'ready';

    public const COMPLETED = 'completed';

    public const ARCHIVED = 'archived';

    public const DEFERRED = 'deferred';

    public const REJECTED = 'rejected';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::REQUESTED,
            self::SAMPLE_RECEIVED,
            self::IN_ANALYSIS,
            self::PENDING_REVIEW,
            self::READY,
            self::COMPLETED,
            self::ARCHIVED,
            self::DEFERRED,
            self::REJECTED,
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        $allowed = [
            self::REQUESTED => [self::SAMPLE_RECEIVED, self::REJECTED, self::DEFERRED],
            self::SAMPLE_RECEIVED => [self::IN_ANALYSIS, self::REJECTED, self::DEFERRED],
            self::IN_ANALYSIS => [self::PENDING_REVIEW, self::REJECTED, self::DEFERRED],
            self::PENDING_REVIEW => [self::READY, self::IN_ANALYSIS, self::REJECTED],
            self::READY => [self::COMPLETED, self::ARCHIVED],
            self::COMPLETED => [self::ARCHIVED],
            self::DEFERRED => [self::REQUESTED, self::IN_ANALYSIS],
            self::REJECTED => [],
            self::ARCHIVED => [],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }
}
