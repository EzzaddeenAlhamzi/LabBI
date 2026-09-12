<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GeneratedReport extends Model
{
    public const TYPE_RESULT_PDF = 'result_pdf';

    protected $fillable = [
        'result_id',
        'test_order_id',
        'patient_id',
        'report_type',
        'storage_disk',
        'storage_key',
        'version',
        'generated_by',
        'generated_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->storage_disk)->path($this->storage_key);
    }
}
