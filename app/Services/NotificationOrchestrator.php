<?php

namespace App\Services;

use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Jobs\DeliverEmailNotificationJob;
use App\Jobs\DeliverWhatsAppNotificationJob;
use App\Jobs\GenerateResultPdfJob;
use App\Models\CriticalAlert;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Result;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * SINGLE authoritative notification orchestration service (M17).
 * Controllers must not create Notification rows directly.
 *
 * External delivery (email / WhatsApp / PDF / patient readiness) ONLY for APPROVED results.
 * Critical Phase 2 in-system alerts remain independent; this may add in-app doctor notify.
 */
class NotificationOrchestrator
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly WorkflowTransitionService $workflow,
    ) {}

    /**
     * Post-approval fan-out: PDF + doctor notify + patient readiness.
     * Safe to call repeatedly — dedupe keys prevent duplicate inbox/delivery rows.
     */
    public function onResultApproved(Result $result, ?User $actor = null): void
    {
        $result->loadMissing(['patient.user', 'order.doctor.user', 'test', 'criticalAlert']);

        if ($result->status !== Result::STATUS_APPROVED) {
            throw new DomainException('لا يُسمح بالتوصيل الخارجي إلا للنتائج المعتمدة.');
        }

        GenerateResultPdfJob::dispatch($result->id, $actor?->id);

        $this->notifyDoctorResultApproved($result);
        $this->notifyPatientResultReady($result);

        $this->finalizeOrderAfterDeliveryKickoff($result);
    }

    /**
     * In-app critical notify for the assigned doctor (independent of external delivery).
     * Does NOT send email/WhatsApp for unapproved results.
     */
    public function onCriticalDetected(CriticalAlert $alert): void
    {
        $alert->loadMissing(['doctor.user', 'patient', 'test', 'order', 'result']);

        $doctorUser = $alert->doctor?->user;
        if (! $doctorUser) {
            return;
        }

        $isCritical = true;
        $dedupe = 'critical_alert:'.$alert->id.':doctor_in_app';

        $notification = $this->createNotification([
            'type' => Notification::TYPE_CRITICAL_ALERT,
            'user_id' => $doctorUser->id,
            'patient_id' => $alert->patient_id,
            'test_order_id' => $alert->test_order_id,
            'result_id' => $alert->result_id,
            'test_id' => $alert->test_id,
            'title' => 'تنبيه قيمة حرجة',
            'message' => sprintf(
                'اكتُشفت قيمة حرجة للمريض %s — الفحص %s (%s %s).',
                $alert->patient?->full_name ?? '#'.$alert->patient_id,
                $alert->test?->name_ar ?? '',
                $alert->detected_value,
                $alert->unit ?? ''
            ),
            'priority' => Notification::PRIORITY_CRITICAL,
            'link_url' => route('doctor.critical.index', absolute: false),
            'dedupe_key' => $dedupe,
        ], $isCritical);

        if ($notification) {
            $this->ensureDelivery(
                $notification,
                NotificationDelivery::CHANNEL_IN_APP,
                (string) $doctorUser->id,
                'critical:'.$alert->id.':in_app'
            )->markSent('in-app');

            $alert->update([
                'notified_at' => now(),
                'notification_status' => 'sent',
            ]);

            $this->audit->log('notifications.critical_in_app', $notification, null, [
                'alert_id' => $alert->id,
                'user_id' => $doctorUser->id,
            ]);
        }
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markRead(Notification $notification, User $user): void
    {
        if ($notification->user_id !== $user->id) {
            throw new DomainException('غير مصرح بقراءة هذا الإشعار.');
        }
        $notification->markRead();
        $this->audit->log('notifications.read', $notification, null, ['user_id' => $user->id], $user->id);
    }

    public function markAllRead(User $user): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->audit->log('notifications.read_all', null, null, ['user_id' => $user->id], $user->id);
    }

    /**
     * Inbox notification helper for structured patient-request workflow (still M17-owned).
     *
     * @param  array<string, mixed>  $attrs
     */
    public function notifyInbox(array $attrs): Notification
    {
        $notification = $this->createNotification($attrs);
        if (! $notification) {
            // existing by dedupe
            return Notification::query()->where('dedupe_key', $attrs['dedupe_key'] ?? '')->firstOrFail();
        }

        $this->ensureDelivery(
            $notification,
            NotificationDelivery::CHANNEL_IN_APP,
            (string) $notification->user_id,
            ($attrs['dedupe_key'] ?? ('inbox:'.$notification->id)).':in_app'
        )->markSent('in-app');

        return $notification;
    }

    private function notifyDoctorResultApproved(Result $result): void
    {
        $doctorUser = $result->order?->doctor?->user;
        if (! $doctorUser) {
            return;
        }

        $critical = $result->classification === ResultClassification::CRITICAL
            || ($result->criticalAlert !== null);

        $notification = $this->createNotification([
            'type' => Notification::TYPE_RESULT_APPROVED,
            'user_id' => $doctorUser->id,
            'patient_id' => $result->patient_id,
            'test_order_id' => $result->test_order_id,
            'result_id' => $result->id,
            'test_id' => $result->test_id,
            'title' => $critical ? 'نتيجة معتمدة (حرجة)' : 'نتيجة معتمدة',
            'message' => sprintf(
                'تم اعتماد نتيجة %s للمريض %s. يمكن فتح التقرير الآمن.',
                $result->test?->name_ar ?? 'فحص',
                $result->patient?->full_name ?? ''
            ),
            'priority' => $critical ? Notification::PRIORITY_CRITICAL : Notification::PRIORITY_NORMAL,
            'link_url' => route('doctor.results.show', $result, absolute: false),
            'dedupe_key' => 'result_approved:'.$result->id.':doctor_in_app',
        ]);

        if (! $notification) {
            return;
        }

        $this->ensureDelivery(
            $notification,
            NotificationDelivery::CHANNEL_IN_APP,
            (string) $doctorUser->id,
            'result_approved:'.$result->id.':doctor_in_app'
        )->markSent('in-app');

        if ($doctorUser->email) {
            $emailDelivery = $this->ensureDelivery(
                $notification,
                NotificationDelivery::CHANNEL_EMAIL,
                $doctorUser->email,
                'result_approved:'.$result->id.':doctor_email'
            );
            if ($emailDelivery->status === NotificationDelivery::STATUS_PENDING) {
                DeliverEmailNotificationJob::dispatch($emailDelivery->id);
            }
        }

        $this->audit->log('notifications.doctor_result_approved', $notification, null, [
            'result_id' => $result->id,
            'priority' => $notification->priority,
        ]);
    }

    private function notifyPatientResultReady(Result $result): void
    {
        $patientUser = $result->patient?->user;
        if (! $patientUser) {
            return;
        }

        $notification = $this->createNotification([
            'type' => Notification::TYPE_RESULT_READY,
            'user_id' => $patientUser->id,
            'patient_id' => $result->patient_id,
            'test_order_id' => $result->test_order_id,
            'result_id' => $result->id,
            'test_id' => $result->test_id,
            'title' => 'نتيجتك جاهزة',
            'message' => sprintf(
                'نتيجة فحص %s جاهزة للعرض والتحميل.',
                $result->test?->name_ar ?? ''
            ),
            'priority' => Notification::PRIORITY_NORMAL,
            'link_url' => route('patient.results.show', $result, absolute: false),
            'dedupe_key' => 'result_ready:'.$result->id.':patient_in_app',
        ]);

        if (! $notification) {
            return;
        }

        $this->ensureDelivery(
            $notification,
            NotificationDelivery::CHANNEL_IN_APP,
            (string) $patientUser->id,
            'result_ready:'.$result->id.':patient_in_app'
        )->markSent('in-app');

        $phone = $result->patient?->phone ?: $patientUser->phone;
        if ($phone) {
            $wa = $this->ensureDelivery(
                $notification,
                NotificationDelivery::CHANNEL_WHATSAPP,
                $phone,
                'result_ready:'.$result->id.':patient_whatsapp'
            );
            if ($wa->status === NotificationDelivery::STATUS_PENDING) {
                DeliverWhatsAppNotificationJob::dispatch($wa->id);
            }
        }

        $this->audit->log('notifications.patient_result_ready', $notification, null, [
            'result_id' => $result->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function createNotification(array $attrs, bool $unused = false): ?Notification
    {
        unset($unused);

        if (! empty($attrs['dedupe_key'])) {
            $existing = Notification::query()->where('dedupe_key', $attrs['dedupe_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($attrs) {
            return Notification::query()->create($attrs);
        });
    }

    private function ensureDelivery(
        Notification $notification,
        string $channel,
        string $recipient,
        string $idempotencyKey
    ): NotificationDelivery {
        $existing = NotificationDelivery::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return NotificationDelivery::query()->create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 0,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * After kickoff, move order completed → archived when all items approved.
     * External delivery failures must NOT roll back approval (jobs handle failures separately).
     */
    private function finalizeOrderAfterDeliveryKickoff(Result $result): void
    {
        $order = $result->order()->with('items.result')->first();
        if (! $order) {
            return;
        }

        $allApproved = $order->items->every(
            fn ($item) => $item->result && $item->result->status === Result::STATUS_APPROVED
        );
        if (! $allApproved) {
            return;
        }

        if (OrderStatus::canTransition($order->status, OrderStatus::COMPLETED)) {
            $this->workflow->transition($order, OrderStatus::COMPLETED, 'بدء التوصيل بعد الاعتماد');
        }

        $order->refresh();
        if (OrderStatus::canTransition($order->status, OrderStatus::ARCHIVED)) {
            $this->workflow->transition($order, OrderStatus::ARCHIVED, 'أرشفة بعد الاعتماد والتوصيل');
        }
    }
}
