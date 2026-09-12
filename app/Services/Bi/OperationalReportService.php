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
     *   summary: array<string, int|string|null>,
     *   rows: LengthAwarePaginator,
     *   filters: BiReportFilters,
     *   limitations: list<string>
     * }
     */
    public function ordersReport(BiReportFilters $filters, int $perPage = 25): array
    {
        $query = TestOrder::query()
            ->with(['patient', 'doctor.user', 'department', 'items.test', 'samples', 'results'])
            ->orderByDesc('ordered_at');

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

        $allForSummary = (clone $query)->get();
        $aggregate = $this->tat->aggregate($allForSummary);

        $statusCounts = $allForSummary->groupBy('status')->map->count()->all();

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
                'orders_count' => $allForSummary->count(),
                'by_status' => $statusCounts,
                'avg_order_tat_seconds' => $aggregate['avg_order_tat_seconds'],
                'avg_order_tat_hours' => TATService::formatHours($aggregate['avg_order_tat_seconds']),
                'approved_results' => Result::query()
                    ->where('status', Result::STATUS_APPROVED)
                    ->whereIn('test_order_id', $allForSummary->pluck('id'))
                    ->count(),
                'critical_linked' => CriticalAlert::query()
                    ->whereIn('test_order_id', $allForSummary->pluck('id'))
                    ->count(),
            ],
            'rows' => $rows,
            'filters' => $filters,
            'limitations' => $aggregate['limitations'],
        ];
    }

    /**
     * Lightweight status volume series for optional charts — same backend metrics as cards.
     *
     * @return Collection<int, array{status: string, label: string, count: int}>
     */
    public function statusVolume(BiReportFilters $filters): Collection
    {
        $query = TestOrder::query()->selectRaw('status, COUNT(*) as aggregate_count');
        if ($filters->dateFrom) {
            $query->where('ordered_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $query->where('ordered_at', '<=', $filters->dateTo);
        }
        if ($filters->departmentId) {
            $query->where('department_id', $filters->departmentId);
        }
        if ($filters->doctorId) {
            $query->where('doctor_id', $filters->doctorId);
        }
        if ($filters->testId) {
            $query->whereHas('items', fn ($q) => $q->where('test_id', $filters->testId));
        }

        return $query->groupBy('status')->get()->map(fn ($row) => [
            'status' => $row->status,
            'label' => TestOrder::statusLabel($row->status),
            'count' => (int) $row->aggregate_count,
        ]);
    }
}
