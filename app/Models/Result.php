<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Result extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'test_order_item_id',
        'test_order_id',
        'patient_id',
        'test_id',
        'value_numeric',
        'value_text',
        'unit',
        'classification',
        'status',
        'notes',
        'entered_by',
        'entered_at',
        'submitted_at',
        'updated_by',
        'approved_by',
        'approved_at',
        'reference_range_id',
        'range_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:4',
            'entered_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'range_snapshot' => 'array',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(TestOrderItem::class, 'test_order_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function referenceRange(): BelongsTo
    {
        return $this->belongsTo(TestReferenceRange::class, 'reference_range_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ResultReview::class);
    }

    public function criticalAlert(): HasOne
    {
        return $this->hasOne(CriticalAlert::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ResultAttachment::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_SUBMITTED, self::STATUS_PENDING_REVIEW => 'بانتظار المراجعة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_RETURNED => 'مُعاد للتصحيح',
            self::STATUS_REJECTED => 'مرفوض',
            default => $status,
        };
    }
}
