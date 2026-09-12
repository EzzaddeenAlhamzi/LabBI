<?php

namespace App\Services;

use App\Models\PatientRequest;
use App\Models\Result;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientRequestService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function submit(User $patientUser, array $data): PatientRequest
    {
        if (! $patientUser->hasPermission('requests.create')) {
            throw new DomainException('غير مصرح بإنشاء طلب.');
        }

        $patient = $patientUser->patient;
        if (! $patient) {
            throw new DomainException('حساب المريض غير مرتبط بملف طبي.');
        }

        $type = $data['type'] ?? '';
        if (! in_array($type, [PatientRequest::TYPE_INTERPRETATION, PatientRequest::TYPE_RETEST], true)) {
            throw ValidationException::withMessages(['type' => 'نوع الطلب غير صالح.']);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            throw ValidationException::withMessages(['message' => 'نص الطلب مطلوب.']);
        }

        $result = null;
        if (! empty($data['result_id'])) {
            $result = Result::query()->with('order')->findOrFail($data['result_id']);
            if ($result->patient_id !== $patient->id || $result->status !== Result::STATUS_APPROVED) {
                throw new DomainException('يمكن الربط فقط بنتيجة معتمدة خاصة بك.');
            }
        }

        $doctorId = $data['doctor_id'] ?? $result?->order?->doctor_id ?? $patient->primary_doctor_id;
        if (! $doctorId) {
            throw ValidationException::withMessages(['doctor_id' => 'يجب تحديد الطبيب.']);
        }

        return DB::transaction(function () use ($patient, $doctorId, $result, $type, $message, $patientUser) {
            $request = PatientRequest::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'result_id' => $result?->id,
                'test_order_id' => $result?->test_order_id,
                'type' => $type,
                'message' => $message,
                'status' => PatientRequest::STATUS_SUBMITTED,
            ]);

            $this->audit->log('requests.submitted', $request, null, [
                'type' => $type,
                'patient_id' => $patient->id,
            ], $patientUser->id);

            $request->loadMissing(['doctor.user', 'patient']);
            $doctorUser = $request->doctor?->user;
            if ($doctorUser) {
                $this->notifications->notifyInbox([
                    'type' => \App\Models\Notification::TYPE_REQUEST_UPDATE,
                    'user_id' => $doctorUser->id,
                    'patient_id' => $request->patient_id,
                    'result_id' => $request->result_id,
                    'test_order_id' => $request->test_order_id,
                    'title' => 'طلب مريض جديد',
                    'message' => sprintf('%s: %s', $request->typeLabel(), mb_substr($request->message, 0, 120)),
                    'priority' => \App\Models\Notification::PRIORITY_NORMAL,
                    'link_url' => route('doctor.requests.index', absolute: false),
                    'dedupe_key' => 'patient_request:'.$request->id.':doctor',
                ]);
            }

            return $request;
        });
    }

    public function setUnderReview(PatientRequest $request, User $actor): PatientRequest
    {
        $this->assertDoctorOwns($request, $actor);
        if ($request->status !== PatientRequest::STATUS_SUBMITTED) {
            throw new DomainException('لا يمكن نقل الطلب إلى قيد المراجعة من الحالة الحالية.');
        }

        $request->update(['status' => PatientRequest::STATUS_UNDER_REVIEW]);
        $this->audit->log('requests.under_review', $request, null, null, $actor->id);
        $this->notifyPatientOfRequestUpdate($request, 'طلبك قيد المراجعة من الطبيب.');

        return $request->refresh();
    }

    public function resolve(PatientRequest $request, User $actor, string $notes): PatientRequest
    {
        $this->assertDoctorOwns($request, $actor);
        if (! in_array($request->status, [PatientRequest::STATUS_SUBMITTED, PatientRequest::STATUS_UNDER_REVIEW], true)) {
            throw new DomainException('لا يمكن حل الطلب من الحالة الحالية.');
        }

        $request->update([
            'status' => PatientRequest::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ]);
        $this->audit->log('requests.resolved', $request, null, ['notes' => $notes], $actor->id);
        $this->notifyPatientOfRequestUpdate($request, 'تم حل طلبك.');

        return $request->refresh();
    }

    public function reject(PatientRequest $request, User $actor, string $notes): PatientRequest
    {
        $this->assertDoctorOwns($request, $actor);
        if (! in_array($request->status, [PatientRequest::STATUS_SUBMITTED, PatientRequest::STATUS_UNDER_REVIEW], true)) {
            throw new DomainException('لا يمكن رفض الطلب من الحالة الحالية.');
        }

        $request->update([
            'status' => PatientRequest::STATUS_REJECTED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ]);
        $this->audit->log('requests.rejected', $request, null, ['notes' => $notes], $actor->id);
        $this->notifyPatientOfRequestUpdate($request, 'تم رفض طلبك.');

        return $request->refresh();
    }

    private function assertDoctorOwns(PatientRequest $request, User $actor): void
    {
        if (! $actor->hasPermission('requests.manage_doctor')) {
            throw new DomainException('غير مصرح بإدارة الطلبات.');
        }
        $doctor = $actor->doctor;
        if (! $doctor || $request->doctor_id !== $doctor->id) {
            throw new DomainException('هذا الطلب ليس ضمن مرضاك.');
        }
    }

    private function notifyPatientOfRequestUpdate(PatientRequest $request, string $message): void
    {
        $request->loadMissing(['patient.user']);
        $patientUser = $request->patient?->user;
        if (! $patientUser) {
            return;
        }

        $this->notifications->notifyInbox([
            'type' => \App\Models\Notification::TYPE_REQUEST_UPDATE,
            'user_id' => $patientUser->id,
            'patient_id' => $request->patient_id,
            'result_id' => $request->result_id,
            'test_order_id' => $request->test_order_id,
            'title' => 'تحديث طلبك',
            'message' => $message,
            'priority' => \App\Models\Notification::PRIORITY_NORMAL,
            'link_url' => route('patient.requests.index', absolute: false),
            'dedupe_key' => 'patient_request:'.$request->id.':patient:'.$request->status,
        ]);
    }
}
