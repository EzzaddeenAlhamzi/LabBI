<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRequest extends Model
{
    public const TYPE_INTERPRETATION = 'interpretation';

    public const TYPE_RETEST = 'retest';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'result_id',
        'test_order_id',
        'type',
        'message',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_INTERPRETATION => 'طلب تفسير',
            self::TYPE_RETEST => 'طلب إعادة فحص',
            default => $this->type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'مُقدَّم',
            self::STATUS_UNDER_REVIEW => 'قيد المراجعة',
            self::STATUS_RESOLVED => 'تم الحل',
            self::STATUS_REJECTED => 'مرفوض',
            default => $this->status,
        };
    }
}
