# LabBI — Local Deployment

**Audience:** developers / reviewers on a workstation  
**OS:** Linux / macOS / Windows (WSL2 recommended)

---

## Prerequisites

- PHP **8.2+** with extensions: `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath`, `sqlite3` and/or `pdo_mysql`  
- Composer 2  
- MySQL 8 / MariaDB **or** SQLite for quick local try  
- Optional: Node/Vite only if rebuilding frontend assets (Blade-first)

---

## Setup

```bash
git clone https://github.com/EzzaddeenAlhamzi/LabBI.git
cd LabBI
composer install
cp .env.example .env
php artisan key:generate
```

### MySQL example

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=labbi
DB_USERNAME=labbi
DB_PASSWORD=secret
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

```bash
php artisan migrate --seed
php artisan serve
```

### Optional workers (local)

```bash
php artisan queue:work
# another terminal:
php artisan schedule:work
```

---

## Demo users

Password for all: `password`

| Email | Role |
|---|---|
| admin@labbi.local | Admin |
| doctor@labbi.local | Doctor |
| lab@labbi.local | Lab technician |
| patient@labbi.local | Patient |

---

## Verify

```bash
php artisan test
curl -s http://127.0.0.1:8000/health
php artisan labbi:backup --trigger=manual
```

Admin UI: سجل العمليات · النسخ الاحتياطي · إعدادات النظام

---

## Notes

- `APP_DEBUG=true` is OK locally; never in production.  
- Private files live under `storage/app/private` — do not `storage:link` sensitive paths into public.  
