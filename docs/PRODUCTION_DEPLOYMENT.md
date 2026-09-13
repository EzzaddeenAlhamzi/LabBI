# LabBI — Production Deployment (provider-neutral)

**Audience:** operators deploying to any Linux cloud/VPS (AWS, GCP, Azure, DigitalOcean, on-prem, etc.)  
**Do not** ship with `APP_DEBUG=true`.

---

## 1. Architecture sketch

```
[Client HTTPS] → [Reverse proxy: TLS, HSTS, HTTP→HTTPS]
                 → [PHP-FPM / app servers]
                 → [MySQL 8]
                 → [Queue workers]
                 → [Cron: schedule:run]
                 → [Private storage volume for attachments/reports/backups]
```

---

## 2. Environment checklist

Copy from `.env.example` and set:

| Category | Keys (examples) | Prod expectation |
|---|---|---|
| App | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_KEY` | Debug **false** |
| HTTPS | `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true` | Required behind TLS |
| Proxies | `TRUSTED_PROXIES` (CSV IPs/CIDRs; avoid `*` unless perimeter-locked) | Empty = no trust |
| Session | `SESSION_DRIVER=database`, `SESSION_SAME_SITE=lax`, `SESSION_HTTP_ONLY=true` | |
| DB | `DB_*` | Least-privilege DB user |
| Queue | `QUEUE_CONNECTION=database` (or redis) | Workers supervised |
| Mail | `MAIL_*` | No secrets in code/UI |
| WhatsApp | `WHATSAPP_DRIVER=…` | Mock until provider chosen |
| Backup | `BACKUP_DISK`, `BACKUP_SCHEDULE_TIME`, retention | Daily verified |
| Logs | `LOG_CHANNEL=stack`, `LOG_STACK=daily`, retention days | |

**Never commit real secrets.** Rotate `APP_KEY` only with a documented re-encrypt plan for encrypted columns.

---

## 3. Reverse proxy notes

Terminate TLS at nginx/Caddy/ALB/etc.:

- Redirect HTTP → HTTPS  
- Set `Strict-Transport-Security` (app also emits HSTS when secure)  
- Forward `X-Forwarded-Proto` / `X-Forwarded-For` / `X-Forwarded-Port` / `X-Forwarded-Host`  
- Configure app `TRUSTED_PROXIES` to the **proxy/LB IP(s) or CIDRs** so Laravel derives client IP and HTTPS correctly  
- Leave `TRUSTED_PROXIES` **empty** when the app is reached directly (local/dev) — prevents clients from spoofing forwarded headers  
- Use `TRUSTED_PROXIES=*` **only** when network policy guarantees clients cannot hit PHP-FPM without the proxy  
- Do not expose `/storage` to private disk paths  
- Optional: CSP/X-Frame at proxy **in addition to** app headers  

Example (nginx → private PHP):

```bash
# .env on app servers
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=10.0.0.10,10.0.0.0/16
```

---

## 4. Deploy steps (generic)

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
# restart php-fpm + queue workers
```

Seed **only** on empty environments (`db:seed`) — never blindly re-seed production.

---

## 5. Required long-running processes

1. **Web** (php-fpm + proxy)  
2. **Queue worker(s)** — email / WhatsApp / PDF / notifications  
3. **Scheduler cron** — daily backup (`labbi:backup`)  

See `OPERATIONS_RUNBOOK.md`.

---

## 6. Health & monitoring

- Probe `GET /health` (DB connectivity; no secrets)  
- Alert on backup failures (`storage/logs/backup-*.log`, `backups.status=failed`)  
- Alert on queue failed jobs  

---

## 7. Backup / restore

Follow `BACKUP_RESTORE_RUNBOOK.md`.  
Run AC-23 restore drill on a **clone/staging DB** before go-live and periodically after.

```bash
# staging only
php artisan labbi:restore-drill --force
```

Production drill blocked unless `ALLOW_RESTORE_DRILL_IN_PRODUCTION=true` (discouraged).

---

## 8. Go-live gate (coordinator)

Independent **A8** security re-cert (Critical/High/Medium = 0) + **A9** final QA must pass before declaring **LABBI PRODUCTION READY**.  
**CAT-04** clinical catalog sign-off remains a separate gate.  
This document alone is not a production certification.
