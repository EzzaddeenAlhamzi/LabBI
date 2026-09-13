<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LabBI operational settings (non-secret)
    |--------------------------------------------------------------------------
    | Secrets (DB passwords, APP_KEY, mail passwords, cloud keys) stay in .env.
    */

    'force_https' => (bool) env('FORCE_HTTPS', false),

    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'schedule' => env('BACKUP_SCHEDULE', 'daily'),
        'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:15'),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'mock'),
    ],

    'security' => [
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),
    ],

    'logging' => [
        'retention_days' => (int) env('LOG_RETENTION_DAYS', 14),
        'note' => 'Do not log passwords, tokens, keys, or unnecessary PHI.',
    ],
];
