# LabBI

نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال

## Stack

- PHP 8.2+ / Laravel 12
- MySQL 8 (SQLite OK for local tests)
- Arabic-first RTL Blade UI
- Queues + scheduler (email / WhatsApp adapter / PDF / daily backup)

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* then:
php artisan migrate --seed
php artisan serve
```

See `docs/LOCAL_DEPLOYMENT.md` and `docs/PRODUCTION_DEPLOYMENT.md`.

### Demo accounts (password: `password`)

| Email | Role |
|---|---|
| admin@labbi.local | Admin |
| doctor@labbi.local | Doctor |
| lab@labbi.local | Laboratory Technician |
| patient@labbi.local | Patient |

## Ops

```bash
php artisan test
php artisan schedule:list
php artisan queue:work --tries=3
php artisan labbi:backup --trigger=manual
curl -s http://127.0.0.1:8000/health
```

## Docs

Planning + phase reports under `docs/` — including Phase 5 security/backup runbooks.

**Production readiness:** Phase 5 implements hardening/audit/backup; independent A8/A9 certification is still required before a PRODUCTION READY verdict.
