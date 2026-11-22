<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LabBI operational settings (non-secret)
    |--------------------------------------------------------------------------
    | Secrets (DB passwords, APP_KEY, mail passwords, cloud keys) stay in .env.
    */

    'force_https' => (bool) env('FORCE_HTTPS', false),

    /*
    | Comma-separated proxy IPs/CIDRs, or "*" only when PHP is unreachable except via the edge.
    | Empty/unset = do not trust X-Forwarded-* (safer default; prevents spoofed client IP / HTTPS).
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

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

    /*
    |--------------------------------------------------------------------------
    | Demo seeding (development / staging only)
    |--------------------------------------------------------------------------
    | Entry: php artisan db:seed --class=LabBIDemoSeeder
    | Never wired into DatabaseSeeder. Refuses APP_ENV=production.
    | Catalog ranges remain demo-only until CAT-04 medical sign-off.
    */
    'demo' => [
        'profile' => strtolower((string) env('LABBI_DEMO_PROFILE', 'small')),
        'seed' => (int) env('LABBI_DEMO_SEED', 42),
        'chunk' => (int) env('LABBI_DEMO_CHUNK', 250),
        'pdf_cap' => [
            'small' => 25,
            'medium' => 75,
            'large' => 150,
        ],
        'profiles' => [
            'small' => [
                'patients' => 50,
                'doctors' => 10,
                'lab_staff' => 5,
                'tests' => 2,
                'orders' => 100,
                'avg_items' => 1.7,
            ],
            'medium' => [
                'patients' => 1000,
                'doctors' => 30,
                'lab_staff' => 15,
                'tests' => 8,
                'orders' => 3000,
                'avg_items' => 1.7,
            ],
            'large' => [
                'patients' => 5000,
                'doctors' => 100,
                'lab_staff' => 40,
                'tests' => 20,
                'orders' => 20000,
                'avg_items' => 1.75,
            ],
        ],
    ],
];
