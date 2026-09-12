<?php

namespace App\Services;

use App\Contracts\PdfReportGenerator;
use App\Models\GeneratedReport;
use App\Models\Result;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates Arabic RTL PDF for APPROVED results only.
 * Presentation (Blade) is separated for future logo/signature/QR/bilingual.
 * Storage keys are UUIDs — never predictable public PHI paths.
 */
class PdfReportService implements PdfReportGenerator
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function generateForApprovedResult(Result $result, ?User $actor = null): GeneratedReport
    {
        $result->loadMissing(['patient', 'test', 'order.doctor.user', 'approvedBy']);

        if ($result->status !== Result::STATUS_APPROVED) {
            throw new DomainException('لا يمكن إنشاء تقرير PDF إلا لنتيجة معتمدة.');
        }

        $idempotencyKey = 'result_pdf:'.$result->id.':v1';
        $existing = GeneratedReport::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $storageKey = 'reports/'.Str::uuid()->toString().'.pdf';
        $html = view('pdf.result-report', ['result' => $result])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        Storage::disk('local')->put($storageKey, $pdf->output());

        $report = GeneratedReport::query()->create([
            'result_id' => $result->id,
            'test_order_id' => $result->test_order_id,
            'patient_id' => $result->patient_id,
            'report_type' => GeneratedReport::TYPE_RESULT_PDF,
            'storage_disk' => 'local',
            'storage_key' => $storageKey,
            'version' => 1,
            'generated_by' => $actor?->id,
            'generated_at' => now(),
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->audit->log('pdf.generated', $report, null, [
            'result_id' => $result->id,
            'storage_key' => $storageKey,
            'version' => 1,
        ], $actor?->id);

        return $report;
    }

    public function assertCanDownload(GeneratedReport $report, User $user): void
    {
        $report->loadMissing('order');

        if ($user->hasRole('doctor')) {
            $doctor = $user->doctor;
            if (! $doctor || $report->order?->doctor_id !== $doctor->id) {
                throw new DomainException('غير مصرح بتنزيل هذا التقرير.');
            }
            if (! $user->hasPermission('pdf.download_clinical')) {
                throw new DomainException('غير مصرح بتنزيل التقارير.');
            }

            return;
        }

        if ($user->hasPermission('pdf.download_own')) {
            $patient = $user->patient;
            if (! $patient || $report->patient_id !== $patient->id) {
                throw new DomainException('غير مصرح بتنزيل هذا التقرير.');
            }

            return;
        }

        if ($user->hasPermission('pdf.download_clinical') || $user->hasPermission('pdf.generate')) {
            return;
        }

        throw new DomainException('غير مصرح بتنزيل التقارير.');
    }
}
