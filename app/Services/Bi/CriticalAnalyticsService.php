<?php

namespace App\Services\Bi;

use App\Domain\Bi\BiReportFilters;
use App\Models\CriticalAlert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Critical cases dashboard — consumes Phase 2 stored classifications/alerts ONLY.
 * Does NOT re-run CriticalValueEngine or duplicate threshold logic.
 */
class CriticalAnalyticsService
{
    public function __construct(
        private readonly TATService $tat,
    ) {}

    /** @return list<string> */
    public static function allowedStatuses(): array
    {
        return [
            CriticalAlert::STATUS_OPEN,
            CriticalAlert::STATUS_ACKNOWLEDGED,
            CriticalAlert::STATUS_CLOSED,
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
    public function dashboard(BiReportFilters $filters, int $perPage = 25): array
    {
        $filters = $filters->ensureReportingWindow();

        $query = CriticalAlert::query()
            ->with(['patient', 'test.department', 'doctor.user', 'order', 'result'])
            ->orderByDesc('discovered_at');

        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();
        $open = (clone $query)->where('status', CriticalAlert::STATUS_OPEN)->count();
        $acknowledged = (clone $query)->where('status', CriticalAlert::STATUS_ACKNOWLEDGED)->count();
        $closed = (clone $query)->where('status', CriticalAlert::STATUS_CLOSED)->count();

        $handling = $this->tat->averageCriticalHandlingFromQuery($query);

        $limitations = [];
        if ($handling['sample_count'] === 0 && $total > 0) {
            $limitations[] = 'لا تتوفر طوابع كافية لاحتساب متوسط معالجة الحالات الحرجة في الفترة.';
        }

        $rows = $query->paginate($perPage)->withQueryString();
        $rows->getCollection()->transform(function (CriticalAlert $alert) {
            $intervals = $this->tat->criticalIntervals($alert);

            return [
                'alert' => $alert,
                'handling_seconds' => $intervals['handling_seconds'],
                'handling_hours' => TATService::formatHours($intervals['handling_seconds']),
                'discovery_to_notify_seconds' => $intervals['discovery_to_notify_seconds'],
                'limitations' => $intervals['limitations'],
            ];
        });

        return [
            'summary' => [
                'total' => $total,
                'open' => $open,
                'acknowledged' => $acknowledged,
                'closed' => $closed,
                'avg_handling_seconds' => $handling['avg_seconds'],
                'avg_handling_hours' => TATService::formatHours($handling['avg_seconds']),
            ],
            'rows' => $rows,
            'filters' => $filters,
            'limitations' => $limitations,
        ];
    }

    private function applyFilters($query, BiReportFilters $filters): void
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
        if ($filters->status) {
            $query->where('status', $filters->status);
        }
        if ($filters->departmentId) {
            $query->whereHas('test', fn ($q) => $q->where('department_id', $filters->departmentId));
        }
        if ($filters->patientId) {
            $query->where('patient_id', $filters->patientId);
        }
    }
}
