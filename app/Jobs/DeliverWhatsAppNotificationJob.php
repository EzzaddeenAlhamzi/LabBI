<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGateway;
use App\Models\NotificationDelivery;
use App\Models\Result;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverWhatsAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId) {}

    public function handle(WhatsAppGateway $gateway, AuditLogger $audit): void
    {
        $delivery = NotificationDelivery::query()->with('notification.result')->find($this->deliveryId);
        if (! $delivery || $delivery->channel !== NotificationDelivery::CHANNEL_WHATSAPP) {
            return;
        }

        if ($delivery->status === NotificationDelivery::STATUS_SENT) {
            return;
        }

        $notification = $delivery->notification;
        $result = $notification?->result;

        if (! $result || $result->status !== Result::STATUS_APPROVED) {
            $delivery->markFailed('Blocked: result not approved');
            $audit->log('whatsapp.delivery_blocked', $delivery, null, [
                'reason' => 'unapproved',
            ]);

            return;
        }

        try {
            $link = route('patient.results.show', $result);
            $message = trim($notification->message.' '.$link);

            $outcome = $gateway->send((string) $delivery->recipient, $message, [
                'result_id' => $result->id,
                'notification_id' => $notification->id,
            ]);

            if (($outcome['status'] ?? '') === 'sent') {
                $delivery->markSent($outcome['provider_ref'] ?? null);
                $audit->log('whatsapp.sent', $delivery, null, [
                    'provider_ref' => $outcome['provider_ref'] ?? null,
                    'result_id' => $result->id,
                ]);
            } else {
                $delivery->markFailed($outcome['error_summary'] ?? 'WhatsApp send failed');
                $audit->log('whatsapp.failed', $delivery, null, [
                    'error_summary' => $outcome['error_summary'] ?? 'failed',
                ]);
            }
        } catch (Throwable $e) {
            Log::error('whatsapp.delivery_failed', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
            $delivery->markFailed($e->getMessage());
            $audit->log('whatsapp.failed', $delivery, null, [
                'error_summary' => mb_substr($e->getMessage(), 0, 200),
            ]);

            throw $e;
        }
    }
}
