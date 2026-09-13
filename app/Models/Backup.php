<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = [
        'taken_at',
        'status',
        'size_bytes',
        'disk',
        'storage_path',
        'trigger',
        'created_by',
        'notes',
        'error_summary',
        'manifest',
        'restored_at',
        'restored_by',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'restored_at' => 'datetime',
            'manifest' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function humanSize(): string
    {
        if ($this->size_bytes === null) {
            return '—';
        }

        $bytes = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
