<?php

namespace App\Domain\Bi;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Reusable server-side filter contract for BI / operational reports / critical dashboard.
 * Controllers must validate through this — never trust raw query params for aggregation.
 *
 * Reporting windows (D-022):
 * - Default period when both dates omitted: last {@see DEFAULT_PERIOD_DAYS} days (inclusive).
 * - Max span when dates present (or after default): {@see MAX_PERIOD_DAYS} days.
 */
final class BiReportFilters
{
    /** Inclusive calendar-day default when no date_from/date_to provided. */
    public const DEFAULT_PERIOD_DAYS = 30;

    /** Inclusive max allowed span between date_from and date_to. */
    public const MAX_PERIOD_DAYS = 366;

    public function __construct(
        public readonly ?Carbon $dateFrom = null,
        public readonly ?Carbon $dateTo = null,
        public readonly ?int $testId = null,
        public readonly ?int $doctorId = null,
        public readonly ?int $departmentId = null,
        public readonly ?string $status = null,
        public readonly ?int $patientId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $allowedStatuses
     */
    public static function fromArray(
        array $input,
        array $allowedStatuses = [],
        bool $applyDefaultPeriod = true,
    ): self {
        $from = self::parseDate($input['date_from'] ?? null, 'date_from', startOfDay: true);
        $to = self::parseDate($input['date_to'] ?? null, 'date_to', startOfDay: false);

        if ($from && $to && $from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'date_from' => 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.',
            ]);
        }

        if ($applyDefaultPeriod) {
            [$from, $to] = self::applyReportingWindow($from, $to);
        } elseif ($from && $to) {
            self::assertMaxPeriod($from, $to);
        }

        $testId = self::optionalPositiveInt($input['test_id'] ?? null, 'test_id');
        $doctorId = self::optionalPositiveInt($input['doctor_id'] ?? null, 'doctor_id');
        $departmentId = self::optionalPositiveInt($input['department_id'] ?? null, 'department_id');
        $patientId = self::optionalPositiveInt($input['patient_id'] ?? null, 'patient_id');

        $status = isset($input['status']) && $input['status'] !== ''
            ? (string) $input['status']
            : null;

        if ($status !== null && $allowedStatuses !== [] && ! in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'حالة غير صالحة للتصفية.',
            ]);
        }

        return new self(
            dateFrom: $from,
            dateTo: $to,
            testId: $testId,
            doctorId: $doctorId,
            departmentId: $departmentId,
            status: $status,
            patientId: $patientId,
        );
    }

    /**
     * Ensure TAT/BI reporting queries always have a bounded date window.
     * Used when filters are constructed without fromArray (e.g. `new BiReportFilters`).
     */
    public function ensureReportingWindow(): self
    {
        [$from, $to] = self::applyReportingWindow($this->dateFrom, $this->dateTo);

        return new self(
            dateFrom: $from,
            dateTo: $to,
            testId: $this->testId,
            doctorId: $this->doctorId,
            departmentId: $this->departmentId,
            status: $this->status,
            patientId: $this->patientId,
        );
    }

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
            'test_id' => $this->testId,
            'doctor_id' => $this->doctorId,
            'department_id' => $this->departmentId,
            'status' => $this->status,
            'patient_id' => $this->patientId,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function applyReportingWindow(?Carbon $from, ?Carbon $to): array
    {
        if ($from === null && $to === null) {
            $to = Carbon::today()->endOfDay();
            $from = Carbon::today()->subDays(self::DEFAULT_PERIOD_DAYS - 1)->startOfDay();
        } elseif ($from === null && $to !== null) {
            $from = $to->copy()->subDays(self::DEFAULT_PERIOD_DAYS - 1)->startOfDay();
        } elseif ($to === null && $from !== null) {
            $to = $from->copy()->addDays(self::DEFAULT_PERIOD_DAYS - 1)->endOfDay();
        }

        self::assertMaxPeriod($from, $to);

        return [$from, $to];
    }

    private static function assertMaxPeriod(Carbon $from, Carbon $to): void
    {
        // Inclusive span in calendar days (start and end on same day = 1).
        $inclusiveDays = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        if ($inclusiveDays > self::MAX_PERIOD_DAYS) {
            throw ValidationException::withMessages([
                'date_to' => 'فترة التقرير تتجاوز الحد الأقصى المسموح ('.self::MAX_PERIOD_DAYS.' يوماً).',
            ]);
        }
    }

    private static function parseDate(mixed $value, string $field, bool $startOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = Carbon::parse((string) $value);

            return $startOfDay ? $date->startOfDay() : $date->endOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'صيغة التاريخ غير صالحة.',
            ]);
        }
    }

    private static function optionalPositiveInt(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value) || (int) $value < 1) {
            throw ValidationException::withMessages([
                $field => 'قيمة المعرّف غير صالحة.',
            ]);
        }

        return (int) $value;
    }
}
