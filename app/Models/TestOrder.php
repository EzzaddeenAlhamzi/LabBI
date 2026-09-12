<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestOrder extends Model
{
    protected $fillable = [
        'order_number',
        'patient_id',
        'doctor_id',
        'department_id',
        'status',
        'ordered_at',
        'analysis_started_at',
        'archived_at',
        'completed_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'analysis_started_at' => 'datetime',
            'archived_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TestOrderItem::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ResultAttachment::class);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'requested' => 'مطلوب',
            'sample_received' => 'تم استلام العينة',
            'in_analysis' => 'قيد التحليل',
            'pending_review' => 'بانتظار المراجعة',
            'ready' => 'جاهز',
            'completed' => 'مكتمل',
            'archived' => 'مؤرشف',
            'deferred' => 'مؤجل',
            'rejected' => 'مرفوض',
            default => $status,
        };
    }
}
