<?php

namespace App\Jobs;

use App\Mail\DoctorResultApprovedMail;
use App\Models\GeneratedReport;
use App\Models\NotificationDelivery;
use App\Models\Result;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverEmailNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId) {}

    public function handle(AuditLogger $audit): void
    {
        $delivery = NotificationDelivery::query()->with('notification.result')->find($this->deliveryId);
        if (! $delivery || $delivery->channel !== NotificationDelivery::CHANNEL_EMAIL) {
            return;
        }

        if ($delivery->status === NotificationDelivery::STATUS_SENT) {
            return; // idempotent
        }

        $notification = $delivery->notification;
        $result = $notification?->result;

        if (! $result || $result->status !== Result::STATUS_APPROVED) {
            $delivery->markFailed('Blocked: result not approved');
            $audit->log('email.delivery_blocked', $delivery, null, [
                'reason' => 'unapproved',
                'result_id' => $result?->id,
            ]);

            return;
        }

        try {
            $report = GeneratedReport::query()
                ->where('result_id', $result->id)
                ->where('report_type', GeneratedReport::TYPE_RESULT_PDF)
                ->latest('id')
                ->first();

            $secureUrl = route('reports.download', ['report' => $report?->id ?? 0]);
            if (! $report) {
                // PDF may still be generating — retry via queue attempts
                if ($this->attempts() < $this->tries) {
                    $this->release(2);

                    return;
                }
                $secureUrl = route('doctor.results.show', $result);
            } else {
                $secureUrl = route('reports.download', $report);
            }

            Mail::to($delivery->recipient)->send(
                new DoctorResultApprovedMail($notification, $result, $secureUrl)
            );

            $delivery->markSent('mail-'.uniqid());
            $audit->log('email.sent', $delivery, null, [
                'notification_id' => $notification->id,
                'result_id' => $result->id,
            ]);
        } catch (Throwable $e) {
            Log::error('email.delivery_failed', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                // never log SMTP secrets
            ]);
            $delivery->markFailed($e->getMessage());
            $audit->log('email.failed', $delivery, null, [
                'error_summary' => mb_substr($e->getMessage(), 0, 200),
            ]);

            throw $e;
        }
    }
}
