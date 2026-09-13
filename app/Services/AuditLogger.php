<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit writer. Never log passwords, tokens, keys, or excess PHI.
 */
class AuditLogger
{
    /** @var list<string> */
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'app_key',
        'authorization',
        'remember_token',
        'mail_password',
        'aws_secret_access_key',
        'db_password',
    ];

    public function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public function security(string $action, ?array $context = null, ?int $userId = null): AuditLog
    {
        Log::channel('security')->info($action, [
            'user_id' => $userId ?? Auth::id(),
            'ip' => Request::ip(),
            'context_keys' => array_keys($context ?? []),
        ]);

        return $this->log($action, null, null, $context, $userId);
    }

    public function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];
        foreach ($values as $key => $value) {
            $lower = strtolower((string) $key);
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lower, $sensitive)) {
                    $clean[$key] = '[redacted]';

                    continue 2;
                }
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);

                continue;
            }

            // Avoid dumping large PHI blobs into the audit UI.
            if (is_string($value) && mb_strlen($value) > 500) {
                $clean[$key] = mb_substr($value, 0, 500).'…';

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
