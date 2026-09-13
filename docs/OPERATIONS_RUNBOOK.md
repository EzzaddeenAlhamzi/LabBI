# LabBI — Operations Runbook (Phase 5)

---

## 1. Process model (production)

| Process | Command | Purpose |
|---|---|---|
| Web (php-fpm / artisan serve) | App server | HTTP |
| Scheduler | `php artisan schedule:run` via cron every minute | Daily backup + future tasks |
| Queue worker | `php artisan queue:work --sleep=1 --tries=3 --timeout=120` | Email, WhatsApp adapter, PDF, notifications |
| Logs | `storage/logs/*.log` | app / security / backup / scheduler / queue / notification |

Supervisor/systemd examples: keep worker + ensure restart on failure.

---

## 2. Queue & notifications

- Default: `QUEUE_CONNECTION=database`  
- Jobs: email / WhatsApp / PDF generation (Phase 3) — retries via worker `--tries`  
- Inspect failures: `php artisan queue:failed`  
- Retry: `php artisan queue:retry all`  
- Idempotency: Phase 3 delivery jobs remain idempotent — failures must not corrupt approvals  

Log channel `notification` / `queue` for ops triage — **no passwords/tokens/PHI dumps**.

---

## 3. Scheduler

```bash
php artisan schedule:list
```

Includes `labbi-daily-backup` at `BACKUP_SCHEDULE_TIME` (default 02:15).

Cron:

```
* * * * * www-data cd /var/www/labbi && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Health

| Endpoint | Meaning |
|---|---|
| `GET /up` | Laravel framework up |
| `GET /health` | JSON: app name, env, time, DB check — **no secrets** |

Alert if `/health` returns 503 or `checks.database=fail`.

---

## 5. Logging channels & retention

| Channel | File | Default retention |
|---|---|---|
| stack/single/daily | `laravel.log` | `LOG_DAILY_DAYS` (14) |
| security | `security.log` | `LOG_RETENTION_DAYS` (14) |
| backup | `backup.log` | 14 |
| scheduler | `scheduler.log` | 14 |
| queue | `queue.log` | 14 |
| notification | `notification.log` | 14 |

**Policy:** never log passwords, API tokens, `APP_KEY`, raw national IDs, or full result payloads unless explicitly required and approved.

---

## 6. Backup / restore

See `BACKUP_RESTORE_RUNBOOK.md`.

---

## 7. Useful ops commands

```bash
php artisan about
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:work --tries=3
php artisan queue:failed
php artisan labbi:backup --trigger=manual
php artisan schedule:list
curl -sS https://YOUR_HOST/health
```

---

## 8. Incident notes

- Spike of `auth.login_failed` / `auth.login_throttled` → possible brute force  
- Backup `status=failed` → disk/permissions/php-zip  
- Queue backlog → scale workers; check mail/WhatsApp provider  
