<?php

namespace App\Domain\Bi;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Reusable server-side filter contract for BI / operational reports / critical dashboard.
 * Controllers must validate through this — never trust raw query params for aggregation.
 */
final class BiReportFilters
{
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
     */
    public static function fromArray(array $input, array $allowedStatuses = []): self
    {
        $from = self::parseDate($input['date_from'] ?? null, 'date_from', startOfDay: true);
        $to = self::parseDate($input['date_to'] ?? null, 'date_to', startOfDay: false);

        if ($from && $to && $from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'date_from' => 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.',
            ]);
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
