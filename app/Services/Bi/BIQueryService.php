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
use App\Models\TestOrderItem;
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
        $filters = ($filters ?? new BiReportFilters)->ensureReportingWindow();
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
     * Period metrics via DB counts / SQL TAT averages — no full-history hydrate.
     *
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
        $filters = $filters->ensureReportingWindow();

        $ordersQuery = TestOrder::query();
        $this->applyOrderFilters($ordersQuery, $filters);

        $alertsQuery = CriticalAlert::query();
        $this->applyAlertFilters($alertsQuery, $filters);

        $ordersInPeriod = (clone $ordersQuery)->count();
        $criticalInPeriod = (clone $alertsQuery)->count();
        $testsInPeriod = TestOrderItem::query()
            ->whereIn('test_order_id', (clone $ordersQuery)->reorder()->select('test_orders.id'))
            ->count();

        $orderTat = $this->tat->averageOrderTatFromQuery($ordersQuery);
        $criticalTat = $this->tat->averageCriticalHandlingFromQuery($alertsQuery);

        $limitations = [];
        if ($orderTat['sample_count'] === 0 && $ordersInPeriod > 0) {
            $limitations[] = 'لا تتوفر طوابع كافية لاحتساب متوسط زمن إنجاز الطلب في الفترة.';
        }
        if ($criticalTat['sample_count'] === 0 && $criticalInPeriod > 0) {
            $limitations[] = 'لا تتوفر طوابع كافية لاحتساب متوسط معالجة الحالات الحرجة في الفترة.';
        }

        return [
            'tests_in_period' => $testsInPeriod,
            'orders_in_period' => $ordersInPeriod,
            'critical_in_period' => $criticalInPeriod,
            'avg_order_tat_seconds' => $orderTat['avg_seconds'],
            'avg_order_tat_hours' => TATService::formatHours($orderTat['avg_seconds']),
            'avg_critical_handling_seconds' => $criticalTat['avg_seconds'],
            'avg_critical_handling_hours' => TATService::formatHours($criticalTat['avg_seconds']),
            'tat_sample_counts' => [
                'order_tat' => $orderTat['sample_count'],
                'critical_handling' => $criticalTat['sample_count'],
            ],
            'limitations' => $limitations,
            'period' => [
                'from' => $filters->dateFrom?->toDateString(),
                'to' => $filters->dateTo?->toDateString(),
            ],
        ];
    }

    /**
     * Daily order counts for dashboard charts — read-only aggregation.
     *
     * @return array{labels: list<string>, counts: list<int>, total: int}
     */
    public function ordersDailySeries(BiReportFilters $filters, int $days = 14): array
    {
        $filters = $filters->ensureReportingWindow();
        $start = Carbon::today()->subDays($days - 1)->startOfDay();

        $rows = TestOrder::query()
            ->where('ordered_at', '>=', $start)
            ->when($filters->dateFrom, fn ($q) => $q->where('ordered_at', '>=', $filters->dateFrom))
            ->when($filters->dateTo, fn ($q) => $q->where('ordered_at', '<=', $filters->dateTo))
            ->when($filters->testId, fn ($q) => $q->whereHas('items', fn ($i) => $i->where('test_id', $filters->testId)))
            ->when($filters->doctorId, fn ($q) => $q->where('doctor_id', $filters->doctorId))
            ->when($filters->departmentId, fn ($q) => $q->where('department_id', $filters->departmentId))
            ->when($filters->status, fn ($q) => $q->where('status', $filters->status))
            ->when($filters->patientId, fn ($q) => $q->where('patient_id', $filters->patientId))
            ->selectRaw('DATE(ordered_at) as day, COUNT(*) as aggregate_count')
            ->groupBy('day')
            ->pluck('aggregate_count', 'day');

        $labels = [];
        $counts = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d/m');
            $counts[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts, 'total' => array_sum($counts)];
    }

    /**
     * Order status distribution for donut charts — read-only aggregation.
     *
     * @return list<array{status: string, label: string, count: int}>
     */
    public function statusBreakdown(BiReportFilters $filters): array
    {
        $filters = $filters->ensureReportingWindow();

        $query = TestOrder::query()->selectRaw('status, COUNT(*) as aggregate_count');
        $this->applyOrderFilters($query, $filters);

        return $query->groupBy('status')->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'label' => TestOrder::statusLabel($row->status),
                'count' => (int) $row->aggregate_count,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Test item counts grouped by laboratory department — read-only aggregation.
     *
     * @return list<array{label: string, count: int}>
     */
    public function departmentWorkload(int $limit = 8): array
    {
        return TestOrderItem::query()
            ->join('tests', 'tests.id', '=', 'test_order_items.test_id')
            ->join('departments', 'departments.id', '=', 'tests.department_id')
            ->selectRaw('departments.name_ar as label, COUNT(*) as aggregate_count')
            ->groupBy('departments.name_ar')
            ->orderByDesc('aggregate_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->aggregate_count])
            ->all();
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

    private function applyAlertFilters($query, BiReportFilters $filters): void
    {
        if ($filters->dateFrom) {
            $query->where('discovered_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $query->where('discovered_at', '<=', $filters->dateTo);
        }
        if ($filters->testId) {
            $query->where('test_id', $filters->testId);
        }
        if ($filters->doctorId) {
            $query->where('doctor_id', $filters->doctorId);
        }
    }
}
