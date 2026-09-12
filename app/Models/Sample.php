<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sample extends Model
{
    protected $fillable = [
        'sample_number',
        'test_order_id',
        'patient_id',
        'test_order_item_id',
        'test_id',
        'received_at',
        'received_by',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TestOrderItem::class, 'test_order_item_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'awaiting' => 'بانتظار الاستلام',
            'received' => 'مستلمة',
            'rejected' => 'مرفوضة',
            'in_analysis' => 'قيد التحليل',
            'completed' => 'مكتملة',
            default => $status,
        };
    }
}
