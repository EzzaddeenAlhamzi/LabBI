<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_CRITICAL = 'critical';

    public const TYPE_RESULT_APPROVED = 'result_approved';

    public const TYPE_RESULT_READY = 'result_ready';

    public const TYPE_CRITICAL_ALERT = 'critical_alert';

    public const TYPE_REQUEST_UPDATE = 'request_update';

    protected $fillable = [
        'type',
        'user_id',
        'patient_id',
        'test_order_id',
        'result_id',
        'test_id',
        'title',
        'message',
        'priority',
        'is_read',
        'read_at',
        'link_url',
        'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function markRead(): void
    {
        if ($this->is_read) {
            return;
        }
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
