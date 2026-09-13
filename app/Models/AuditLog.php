<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Audit logs are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit logs are append-only and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Safe metadata for Admin UI — never exposes secret-looking keys.
     */
    public function safeMetadata(): array
    {
        return [
            'old' => $this->redactForDisplay($this->old_values ?? []),
            'new' => $this->redactForDisplay($this->new_values ?? []),
        ];
    }

    protected function redactForDisplay(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $lower = strtolower((string) $key);
            if (
                str_contains($lower, 'password')
                || str_contains($lower, 'token')
                || str_contains($lower, 'secret')
                || str_contains($lower, 'api_key')
                || str_contains($lower, 'national_id')
            ) {
                $out[$key] = '[محجوب]';

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->redactForDisplay($value);

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
