# LabBI

نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال

## Stack (Phase 1)

- PHP 8.3 + Laravel 12
- MySQL / MariaDB
- Arabic-first RTL Blade UI

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* then:
php artisan migrate --seed
php artisan serve
```

### Demo accounts (password: `password`)

| Email | Role |
|---|---|
| admin@labbi.local | Admin |
| doctor@labbi.local | Doctor |
| lab@labbi.local | Laboratory Technician |
| patient@labbi.local | Patient |

## Docs

See `docs/` for planning, decisions, and `docs/PHASE1_COMPLETION_REPORT.md`.

## Tests

```bash
php artisan test
```
