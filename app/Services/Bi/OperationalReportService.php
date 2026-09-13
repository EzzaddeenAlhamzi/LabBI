<?php

namespace App\Services\Bi;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Result;
use App\Models\TestOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Operational reports — server-side filtered read models (separate from Phase 3 clinical PDFs).
 */
class OperationalReportService
{
    public function __construct(
        private readonly TATService $tat,
    ) {}

    /** @return list<string> */
    public static function allowedOrderStatuses(): array
    {
        return [
            OrderStatus::REQUESTED,
            OrderStatus::SAMPLE_RECEIVED,
            OrderStatus::IN_ANALYSIS,
            OrderStatus::PENDING_REVIEW,
            OrderStatus::READY,
            OrderStatus::COMPLETED,
            OrderStatus::ARCHIVED,
            OrderStatus::DEFERRED,
            OrderStatus::REJECTED,
        ];
    }

    /**
     * @return array{
     *   summary: array<string, int|string|null|array>,
     *   rows: LengthAwarePaginator,
     *   filters: BiReportFilters,
     *   limitations: list<string>
     * }
     */
    public function ordersReport(BiReportFilters $filters, int $perPage = 25): array
    {
        $filters = $filters->ensureReportingWindow();

        $query = TestOrder::query()
            ->with(['patient', 'doctor.user', 'department', 'items.test', 'samples', 'results'])
            ->orderByDesc('ordered_at');

        $this->applyFilters($query, $filters);

        $ordersCount = (clone $query)->count();

        $statusCounts = (clone $query)
            ->reorder()
            ->selectRaw('status, COUNT(*) as aggregate_count')
            ->groupBy('status')
            ->pluck('aggregate_count', 'status')
            ->map(fn ($c) => (int) $c)
            ->all();

        $orderIdsSub = (clone $query)->reorder()->select('test_orders.id');
        $orderTat = $this->tat->averageOrderTatFromQuery($query);

        $approvedResults = Result::query()
            ->where('status', Result::STATUS_APPROVED)
            ->whereIn('test_order_id', $orderIdsSub)
            ->count();

        $criticalLinked = CriticalAlert::query()
            ->whereIn('test_order_id', $orderIdsSub)
            ->count();

        $limitations = [];
        if ($orderTat['sample_count'] === 0 && $ordersCount > 0) {
            $limitations[] = 'لا تتوفر طوابع كافية لاحتساب متوسط زمن إنجاز الطلب في الفترة.';
        }

        $rows = $query->paginate($perPage)->withQueryString();
        $rows->getCollection()->transform(function (TestOrder $order) {
            $tat = $this->tat->orderIntervals($order);

            return [
                'order' => $order,
                'tat_seconds' => $tat['intervals']['order_tat']['seconds'] ?? null,
                'tat_hours' => TATService::formatHours($tat['intervals']['order_tat']['seconds'] ?? null),
                'limitations' => $tat['limitations'],
            ];
        });

        return [
            'summary' => [
                'orders_count' => $ordersCount,
                'by_status' => $statusCounts,
                'avg_order_tat_seconds' => $orderTat['avg_seconds'],
                'avg_order_tat_hours' => TATService::formatHours($orderTat['avg_seconds']),
                'approved_results' => $approvedResults,
                'critical_linked' => $criticalLinked,
            ],
            'rows' => $rows,
            'filters' => $filters,
            'limitations' => $limitations,
        ];
    }

    /**
     * Lightweight status volume series for optional charts — same backend metrics as cards.
     *
     * @return Collection<int, array{status: string, label: string, count: int}>
     */
    public function statusVolume(BiReportFilters $filters): Collection
    {
        $filters = $filters->ensureReportingWindow();

        $query = TestOrder::query()->selectRaw('status, COUNT(*) as aggregate_count');
        $this->applyFilters($query, $filters);

        return $query->groupBy('status')->get()->map(fn ($row) => [
            'status' => $row->status,
            'label' => TestOrder::statusLabel($row->status),
            'count' => (int) $row->aggregate_count,
        ]);
    }

    private function applyFilters($query, BiReportFilters $filters): void
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
