<?php

namespace App\Services\Bi;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Bi\TrendCalculator;
use App\Domain\Critical\ResultClassification;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Result;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Historical comparison for doctors — approved results only + ACL / patient isolation.
 * Trends are NON-medical (see TrendCalculator). No diagnosis language.
 */
class HistoricalComparisonService
{
    public function __construct(
        private readonly TrendCalculator $trends,
    ) {}

    /**
     * Assert doctor may compare this patient (primary or prior orders).
     * Admins with comparison.use may compare any patient.
     */
    public function assertCanCompare(User $actor, Patient $patient): void
    {
        if (! $actor->hasPermission('comparison.use')) {
            throw new DomainException('ليست لديك صلاحية المقارنة التاريخية.');
        }

        if ($actor->hasRole('admin') || $actor->hasPermission('patients.view_all')) {
            return;
        }

        $doctor = $actor->doctor;
        if (! $doctor) {
            throw new DomainException('حساب الطبيب غير مرتبط.');
        }

        if (! $this->doctorCanAccessPatient($doctor, $patient)) {
            throw new DomainException('لا يمكنك مقارنة نتائج مريض غير مخوّل لك.');
        }
    }

    public function doctorCanAccessPatient(Doctor $doctor, Patient $patient): bool
    {
        if ($patient->primary_doctor_id === $doctor->id) {
            return true;
        }

        return $patient->orders()->where('doctor_id', $doctor->id)->exists();
    }

    /**
     * Patients available for the actor's comparison picker.
     *
     * @return Collection<int, Patient>
     */
    public function accessiblePatients(User $actor): Collection
    {
        if ($actor->hasRole('admin') || $actor->hasPermission('patients.view_all')) {
            return Patient::query()->orderBy('full_name')->limit(200)->get();
        }

        $doctor = $actor->doctor;
        if (! $doctor) {
            return collect();
        }

        return Patient::query()
            ->where('primary_doctor_id', $doctor->id)
            ->orWhereHas('orders', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->orderBy('full_name')
            ->distinct()
            ->limit(200)
            ->get();
    }

    /**
     * @return array{
     *   patient: Patient,
     *   test_id: ?int,
     *   rows: list<array{
     *     date: string,
     *     value: mixed,
     *     unit: ?string,
     *     classification: string,
     *     classification_label: string,
     *     reference_range: ?string,
     *     critical_status: string,
     *     result_id: int,
     *     order_id: int
     *   }>,
     *   trend: array<string, mixed>,
     *   filters: BiReportFilters,
     *   limitations: list<string>
     * }
     */
    public function compare(User $actor, Patient $patient, BiReportFilters $filters): array
    {
        $this->assertCanCompare($actor, $patient);

        $limitations = [];
        if ($filters->testId === null) {
            $limitations[] = 'اختر فحصاً لعرض المقارنة والاتجاه.';
        }

        $query = Result::query()
            ->with(['test', 'criticalAlert', 'order'])
            ->where('patient_id', $patient->id)
            ->where('status', Result::STATUS_APPROVED)
            ->orderBy('approved_at')
            ->orderBy('id');

        // Doctor isolation: only results from orders assigned to this doctor (or primary),
        // unless admin / patients.view_all.
        if (! $actor->hasRole('admin') && ! $actor->hasPermission('patients.view_all')) {
            $doctor = $actor->doctor;
            $query->whereHas('order', fn ($q) => $q->where('doctor_id', $doctor->id));
        }

        if ($filters->testId) {
            $query->where('test_id', $filters->testId);
        }
        if ($filters->dateFrom) {
            $query->where('approved_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $query->where('approved_at', '<=', $filters->dateTo);
        }

        $results = $query->get();
        $rows = [];
        $points = [];

        foreach ($results as $result) {
            $snapshot = $result->range_snapshot ?? [];
            $ref = $this->formatReferenceRange($snapshot);
            $criticalStatus = $result->criticalAlert
                ? $result->criticalAlert->status
                : ($result->classification === ResultClassification::CRITICAL ? 'unlinked' : 'none');

            $rows[] = [
                'date' => $result->approved_at?->toDateTimeString() ?? $result->created_at?->toDateTimeString(),
                'value' => $result->value_numeric ?? $result->value_text,
                'unit' => $result->unit,
                'classification' => $result->classification,
                'classification_label' => ResultClassification::labelAr($result->classification),
                'reference_range' => $ref,
                'critical_status' => $criticalStatus,
                'result_id' => $result->id,
                'order_id' => $result->test_order_id,
            ];

            $points[] = [
                'value' => $result->value_numeric,
                'unit' => $result->unit,
            ];
        }

        $trend = $filters->testId
            ? $this->trends->calculate($points)
            : [
                'trend' => 'insufficient_data',
                'label_ar' => 'بيانات غير كافية',
                'color' => '#4d6358',
                'limitation' => 'يلزم اختيار فحص واحد.',
                'first' => null,
                'last' => null,
                'relative_change' => null,
            ];

        if (! empty($trend['limitation'])) {
            $limitations[] = $trend['limitation'];
        }

        if ($rows === [] && $filters->testId) {
            $limitations[] = 'لا توجد نتائج معتمدة مطابقة للتصفية.';
        }

        return [
            'patient' => $patient,
            'test_id' => $filters->testId,
            'rows' => $rows,
            'trend' => $trend,
            'filters' => $filters,
            'limitations' => array_values(array_unique($limitations)),
        ];
    }

    /** @param  array<string, mixed>  $snapshot */
    private function formatReferenceRange(array $snapshot): ?string
    {
        $low = $snapshot['normal_low'] ?? $snapshot['reference_low'] ?? null;
        $high = $snapshot['normal_high'] ?? $snapshot['reference_high'] ?? null;
        $unit = $snapshot['unit'] ?? null;

        if ($low === null && $high === null) {
            return null;
        }

        $text = trim(($low ?? '—').' – '.($high ?? '—'));
        if ($unit) {
            $text .= ' '.$unit;
        }

        return $text;
    }
}
