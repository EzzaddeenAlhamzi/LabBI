<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'notification_id',
        'channel',
        'recipient',
        'status',
        'attempts',
        'provider_ref',
        'error_summary',
        'sent_at',
        'failed_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function markSent(?string $providerRef = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'provider_ref' => $providerRef ?? $this->provider_ref,
            'sent_at' => now(),
            'failed_at' => null,
            'error_summary' => null,
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markFailed(string $errorSummary): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_summary' => mb_substr($errorSummary, 0, 500),
            'attempts' => $this->attempts + 1,
        ]);
    }
}
