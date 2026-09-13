<?php

namespace App\Services\Bi;

use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * TATService — authoritative turnaround-time calculations from REAL timestamps only.
 *
 * Documented formulas (seconds; displayed as hours when aggregated):
 *
 * | Interval | Start | End | Notes |
 * |---|---|---|---|
 * | Order → Sample | test_orders.ordered_at | samples.received_at (earliest for order) | Skip if either null |
 * | Sample → Analysis | samples.received_at | test_orders.analysis_started_at | Skip if either null |
 * | Analysis → Submit | test_orders.analysis_started_at | results.submitted_at | Per result; skip if null |
 * | Submit → Approval | results.submitted_at ?? results.entered_at | results.approved_at | Per approved result |
 * | Order TAT | test_orders.ordered_at | results.approved_at (latest) OR test_orders.completed_at | Prefer completed_at when set |
 * | Critical discovery → notify | critical_alerts.discovered_at | critical_alerts.notified_at | Skip if null |
 * | Critical notify → ack | critical_alerts.notified_at | critical_alerts.acknowledged_at | Skip if null |
 * | Critical handling | critical_alerts.discovered_at | notified_at ?? acknowledged_at | Prefer notified_at for “handling” |
 *
 * Missing timestamps → interval omitted + limitation string. NEVER invent times.
 */
class TATService
{
    /**
     * Seconds between two timestamps, or null if either missing / inverted.
     */
    public function secondsBetween(?CarbonInterface $from, ?CarbonInterface $to): ?int
    {
        if ($from === null || $to === null) {
            return null;
        }

        if ($to->lessThan($from)) {
            return null;
        }

        return (int) $from->diffInSeconds($to);
    }

    /**
     * @return array{
     *   order_id: int,
     *   intervals: array<string, array{seconds: ?int, from: ?string, to: ?string, limitation: ?string}>,
     *   limitations: list<string>
     * }
     */
    public function orderIntervals(TestOrder $order): array
    {
        $order->loadMissing(['samples', 'results']);
        $limitations = [];
        $intervals = [];

        $orderedAt = $order->ordered_at;
        $sampleReceived = $order->samples
            ->filter(fn (Sample $s) => $s->received_at !== null)
            ->sortBy('received_at')
            ->first()?->received_at;
        $analysisStarted = $order->analysis_started_at;
        $completedAt = $order->completed_at;

        $intervals['order_to_sample'] = $this->interval(
            $orderedAt,
            $sampleReceived,
            'ordered_at',
            'samples.received_at',
            $limitations,
        );

        $intervals['sample_to_analysis'] = $this->interval(
            $sampleReceived,
            $analysisStarted,
            'samples.received_at',
            'analysis_started_at',
            $limitations,
        );

        $approvedResults = $order->results->where('status', Result::STATUS_APPROVED);
        $latestApprovedAt = $approvedResults
            ->filter(fn (Result $r) => $r->approved_at !== null)
            ->sortByDesc('approved_at')
            ->first()?->approved_at;

        $orderEnd = $completedAt ?? $latestApprovedAt;
        $intervals['order_tat'] = $this->interval(
            $orderedAt,
            $orderEnd,
            'ordered_at',
            $completedAt ? 'completed_at' : 'results.approved_at',
            $limitations,
            $completedAt === null && $latestApprovedAt === null
                ? 'لا يوجد completed_at ولا approved_at لهذا الطلب.'
                : null,
        );

        $submitSamples = [];
        $approvalSamples = [];
        foreach ($approvedResults as $result) {
            $submitAt = $result->submitted_at ?? $result->entered_at;
            $submitSec = $this->secondsBetween($analysisStarted, $submitAt);
            if ($submitSec !== null) {
                $submitSamples[] = $submitSec;
            }
            $approvalSec = $this->secondsBetween($submitAt, $result->approved_at);
            if ($approvalSec !== null) {
                $approvalSamples[] = $approvalSec;
            }
        }

        $intervals['analysis_to_submit'] = [
            'seconds' => $submitSamples === [] ? null : (int) round(array_sum($submitSamples) / count($submitSamples)),
            'from' => $analysisStarted?->toIso8601String(),
            'to' => 'results.submitted_at|entered_at (avg)',
            'limitation' => $submitSamples === []
                ? 'لا تتوفر طوابع analysis_started_at و/أو submitted_at لحساب الفاصل.'
                : null,
        ];
        if ($intervals['analysis_to_submit']['limitation']) {
            $limitations[] = $intervals['analysis_to_submit']['limitation'];
        }

        $intervals['submit_to_approval'] = [
            'seconds' => $approvalSamples === [] ? null : (int) round(array_sum($approvalSamples) / count($approvalSamples)),
            'from' => 'results.submitted_at|entered_at',
            'to' => 'results.approved_at (avg)',
            'limitation' => $approvalSamples === []
                ? 'لا تتوفر طوابع submitted_at/entered_at و approved_at لحساب فاصل الاعتماد.'
                : null,
        ];
        if ($intervals['submit_to_approval']['limitation']) {
            $limitations[] = $intervals['submit_to_approval']['limitation'];
        }

        return [
            'order_id' => $order->id,
            'intervals' => $intervals,
            'limitations' => array_values(array_unique($limitations)),
        ];
    }

    /**
     * @return array{
     *   alert_id: int,
     *   discovery_to_notify_seconds: ?int,
     *   notify_to_ack_seconds: ?int,
     *   handling_seconds: ?int,
     *   limitations: list<string>
     * }
     */
    public function criticalIntervals(CriticalAlert $alert): array
    {
        $limitations = [];
        $discoveryToNotify = $this->secondsBetween($alert->discovered_at, $alert->notified_at);
        if ($discoveryToNotify === null) {
            $limitations[] = 'حرج: discovered_at و/أو notified_at مفقود — لا يُحسب فاصل الإشعار.';
        }

        $notifyToAck = $this->secondsBetween($alert->notified_at, $alert->acknowledged_at);
        if ($notifyToAck === null && $alert->acknowledged_at === null) {
            $limitations[] = 'حرج: لم يُسجَّل acknowledged_at بعد.';
        } elseif ($notifyToAck === null) {
            $limitations[] = 'حرج: notified_at مفقود — لا يُحسب فاصل الإقرار.';
        }

        $handlingEnd = $alert->notified_at ?? $alert->acknowledged_at;
        $handling = $this->secondsBetween($alert->discovered_at, $handlingEnd);
        if ($handling === null) {
            $limitations[] = 'حرج: لا يمكن احتساب زمن المعالجة بدون discovered_at ونقطة نهاية (notified_at/acknowledged_at).';
        }

        return [
            'alert_id' => $alert->id,
            'discovery_to_notify_seconds' => $discoveryToNotify,
            'notify_to_ack_seconds' => $notifyToAck,
            'handling_seconds' => $handling,
            'limitations' => $limitations,
        ];
    }

    /**
     * Aggregate averages over a set of orders / alerts for a period.
     *
     * @param  iterable<TestOrder>  $orders
     * @param  iterable<CriticalAlert>  $alerts
     * @return array{
     *   avg_order_tat_seconds: ?int,
     *   avg_order_to_sample_seconds: ?int,
     *   avg_sample_to_analysis_seconds: ?int,
     *   avg_submit_to_approval_seconds: ?int,
     *   avg_critical_handling_seconds: ?int,
     *   sample_counts: array<string, int>,
     *   limitations: list<string>
     * }
     */
    public function aggregate(iterable $orders, iterable $alerts = []): array
    {
        $buckets = [
            'order_tat' => [],
            'order_to_sample' => [],
            'sample_to_analysis' => [],
            'submit_to_approval' => [],
            'critical_handling' => [],
        ];
        $limitations = [];

        foreach ($orders as $order) {
            $detail = $this->orderIntervals($order);
            foreach (['order_tat', 'order_to_sample', 'sample_to_analysis', 'submit_to_approval'] as $key) {
                $seconds = $detail['intervals'][$key]['seconds'] ?? null;
                if ($seconds !== null) {
                    $buckets[$key][] = $seconds;
                }
            }
            foreach ($detail['limitations'] as $limitation) {
                $limitations[] = $limitation;
            }
        }

        foreach ($alerts as $alert) {
            $detail = $this->criticalIntervals($alert);
            if ($detail['handling_seconds'] !== null) {
                $buckets['critical_handling'][] = $detail['handling_seconds'];
            }
            foreach ($detail['limitations'] as $limitation) {
                $limitations[] = $limitation;
            }
        }

        return [
            'avg_order_tat_seconds' => $this->avg($buckets['order_tat']),
            'avg_order_to_sample_seconds' => $this->avg($buckets['order_to_sample']),
            'avg_sample_to_analysis_seconds' => $this->avg($buckets['sample_to_analysis']),
            'avg_submit_to_approval_seconds' => $this->avg($buckets['submit_to_approval']),
            'avg_critical_handling_seconds' => $this->avg($buckets['critical_handling']),
            'sample_counts' => [
                'order_tat' => count($buckets['order_tat']),
                'order_to_sample' => count($buckets['order_to_sample']),
                'sample_to_analysis' => count($buckets['sample_to_analysis']),
                'submit_to_approval' => count($buckets['submit_to_approval']),
                'critical_handling' => count($buckets['critical_handling']),
            ],
            'limitations' => array_values(array_unique($limitations)),
        ];
    }

    public static function formatHours(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        return number_format($seconds / 3600, 2).' ساعة';
    }

    /**
     * DB-side average order TAT matching {@see orderIntervals} formula:
     * ordered_at → completed_at preferred, else latest approved_at.
     * Does not hydrate order models.
     *
     * @param  Builder<TestOrder>  $ordersQuery
     * @return array{avg_seconds: ?int, sample_count: int}
     */
    public function averageOrderTatFromQuery(Builder $ordersQuery): array
    {
        $approved = Result::STATUS_APPROVED;
        $endSql = "COALESCE(test_orders.completed_at, (
            SELECT MAX(r.approved_at) FROM results AS r
            WHERE r.test_order_id = test_orders.id AND r.status = '{$approved}'
        ))";
        $diffSql = $this->sqlSecondsBetweenExpr('test_orders.ordered_at', $endSql);

        $row = DB::table('test_orders')
            ->whereIn('id', (clone $ordersQuery)->reorder()->select('test_orders.id'))
            ->whereNotNull('test_orders.ordered_at')
            ->whereRaw("{$endSql} IS NOT NULL")
            ->whereRaw("{$endSql} >= test_orders.ordered_at")
            ->selectRaw("AVG({$diffSql}) as avg_seconds, COUNT(*) as sample_count")
            ->first();

        $avg = $row?->avg_seconds;

        return [
            'avg_seconds' => $avg === null ? null : (int) round((float) $avg),
            'sample_count' => (int) ($row?->sample_count ?? 0),
        ];
    }

    /**
     * DB-side average critical handling matching {@see criticalIntervals}:
     * discovered_at → notified_at preferred, else acknowledged_at.
     *
     * @param  Builder<CriticalAlert>  $alertsQuery
     * @return array{avg_seconds: ?int, sample_count: int}
     */
    public function averageCriticalHandlingFromQuery(Builder $alertsQuery): array
    {
        $endSql = 'COALESCE(critical_alerts.notified_at, critical_alerts.acknowledged_at)';
        $diffSql = $this->sqlSecondsBetweenExpr('critical_alerts.discovered_at', $endSql);

        $row = DB::table('critical_alerts')
            ->whereIn('id', (clone $alertsQuery)->reorder()->select('critical_alerts.id'))
            ->whereNotNull('critical_alerts.discovered_at')
            ->whereRaw("{$endSql} IS NOT NULL")
            ->whereRaw("{$endSql} >= critical_alerts.discovered_at")
            ->selectRaw("AVG({$diffSql}) as avg_seconds, COUNT(*) as sample_count")
            ->first();

        $avg = $row?->avg_seconds;

        return [
            'avg_seconds' => $avg === null ? null : (int) round((float) $avg),
            'sample_count' => (int) ($row?->sample_count ?? 0),
        ];
    }

    /** Portable seconds-between expression (SQLite tests + MySQL production). */
    public function sqlSecondsBetweenExpr(string $startSql, string $endSql): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "CAST((julianday({$endSql}) - julianday({$startSql})) * 86400 AS REAL)";
        }

        return "TIMESTAMPDIFF(SECOND, {$startSql}, {$endSql})";
    }

    /** @param  list<int>  $values */
    private function avg(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        return (int) round(array_sum($values) / count($values));
    }

    /**
     * @param  list<string>  $limitations
     * @return array{seconds: ?int, from: ?string, to: ?string, limitation: ?string}
     */
    private function interval(
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        string $fromLabel,
        string $toLabel,
        array &$limitations,
        ?string $customLimitation = null,
    ): array {
        $seconds = $this->secondsBetween($from, $to);
        $limitation = null;
        if ($seconds === null) {
            $limitation = $customLimitation ?? "فاصل {$fromLabel} → {$toLabel} غير متاح (طابع مفقود أو ترتيب غير صالح).";
            $limitations[] = $limitation;
        }

        return [
            'seconds' => $seconds,
            'from' => $from instanceof Carbon ? $from->toIso8601String() : ($from?->toIso8601String()),
            'to' => $to instanceof Carbon ? $to->toIso8601String() : ($to?->toIso8601String()),
            'limitation' => $limitation,
        ];
    }
}
