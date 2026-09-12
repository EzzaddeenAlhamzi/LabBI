<?php

namespace App\Services\Bi;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\TestOrder;
use Carbon\Carbon;

/**
 * Admin BI dashboard metrics — read-only aggregations from transactional tables.
 * Controllers must consume this service only (no scattered ad-hoc counts).
 */
class BIQueryService
{
    public function __construct(
        private readonly TATService $tat,
    ) {}

    /**
     * Real DB-backed dashboard cards (BI-01 / AC-20).
     *
     * @return array{
     *   cards: array<string, int>,
     *   performance: array<string, mixed>,
     *   generated_at: string
     * }
     */
    public function dashboard(?BiReportFilters $filters = null): array
    {
        $filters ??= new BiReportFilters;
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        $ordersToday = TestOrder::query()
            ->whereBetween('ordered_at', [$todayStart, $todayEnd])
            ->count();

        $cards = [
            'patients' => Patient::query()->count(),
            'doctors' => Doctor::query()->count(),
            'tests_catalog' => LabTest::query()->where('is_active', true)->count(),
            'orders_today' => $ordersToday,
            'samples_awaiting' => Sample::query()->where('status', 'awaiting')->count(),
            'in_analysis' => TestOrder::query()->where('status', OrderStatus::IN_ANALYSIS)->count(),
            'pending_review' => TestOrder::query()->where('status', OrderStatus::PENDING_REVIEW)->count(),
            'ready_completed' => TestOrder::query()
                ->whereIn('status', [OrderStatus::READY, OrderStatus::COMPLETED, OrderStatus::ARCHIVED])
                ->count(),
            'deferred' => TestOrder::query()->where('status', OrderStatus::DEFERRED)->count(),
            'rejected' => TestOrder::query()->where('status', OrderStatus::REJECTED)->count(),
            'critical_cases' => CriticalAlert::query()->count(),
            'critical_open' => CriticalAlert::query()->where('status', CriticalAlert::STATUS_OPEN)->count(),
        ];

        $performance = $this->performanceMetrics($filters);

        return [
            'cards' => $cards,
            'performance' => $performance,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *   tests_in_period: int,
     *   orders_in_period: int,
     *   critical_in_period: int,
     *   avg_order_tat_seconds: ?int,
     *   avg_order_tat_hours: ?string,
     *   avg_critical_handling_seconds: ?int,
     *   avg_critical_handling_hours: ?string,
     *   tat_sample_counts: array<string, int>,
     *   limitations: list<string>,
     *   period: array{from: ?string, to: ?string}
     * }
     */
    public function performanceMetrics(BiReportFilters $filters): array
    {
        $ordersQuery = TestOrder::query()->with(['samples', 'results']);
        $this->applyOrderFilters($ordersQuery, $filters);
        $orders = $ordersQuery->get();

        $alertsQuery = CriticalAlert::query();
        if ($filters->dateFrom) {
            $alertsQuery->where('discovered_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $alertsQuery->where('discovered_at', '<=', $filters->dateTo);
        }
        if ($filters->testId) {
            $alertsQuery->where('test_id', $filters->testId);
        }
        if ($filters->doctorId) {
            $alertsQuery->where('doctor_id', $filters->doctorId);
        }
        $alerts = $alertsQuery->get();

        $aggregate = $this->tat->aggregate($orders, $alerts);

        $testsInPeriod = $orders->sum(fn (TestOrder $o) => $o->items()->count());

        return [
            'tests_in_period' => (int) $testsInPeriod,
            'orders_in_period' => $orders->count(),
            'critical_in_period' => $alerts->count(),
            'avg_order_tat_seconds' => $aggregate['avg_order_tat_seconds'],
            'avg_order_tat_hours' => TATService::formatHours($aggregate['avg_order_tat_seconds']),
            'avg_critical_handling_seconds' => $aggregate['avg_critical_handling_seconds'],
            'avg_critical_handling_hours' => TATService::formatHours($aggregate['avg_critical_handling_seconds']),
            'tat_sample_counts' => $aggregate['sample_counts'],
            'limitations' => $aggregate['limitations'],
            'period' => [
                'from' => $filters->dateFrom?->toDateString(),
                'to' => $filters->dateTo?->toDateString(),
            ],
        ];
    }

    private function applyOrderFilters($query, BiReportFilters $filters): void
    {
        if ($filters->dateFrom) {
            $query->where('ordered_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $query->where('ordered_at', '<=', $filters->dateTo);
        }
        if ($filters->testId) {
            $query->whereHas('items', fn ($q) => $q->where('test_id', $filters->testId));
        }
        if ($filters->doctorId) {
            $query->where('doctor_id', $filters->doctorId);
        }
        if ($filters->departmentId) {
            $query->where('department_id', $filters->departmentId);
        }
        if ($filters->status) {
            $query->where('status', $filters->status);
        }
        if ($filters->patientId) {
            $query->where('patient_id', $filters->patientId);
        }
    }
}
