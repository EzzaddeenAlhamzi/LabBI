# LabBI — Backup & Restore Runbook

**AC-22 / AC-23** · Phase 5  
**Destructive restore — use change control in production.**

---

## 1. What is backed up

| Component | Content |
|---|---|
| Database | Portable JSON dump of all app tables (except `migrations`) |
| Files | Private disk prefixes `attachments/`, `reports/` |
| Metadata | `backups` row: taken_at, status, size_bytes, disk, storage_path, trigger, notes, manifest |

Archive format: ZIP on `BACKUP_DISK` (default `local` → `storage/app/private/backups/...`).

---

## 2. Configuration (env only)

```env
BACKUP_DISK=local
BACKUP_SCHEDULE=daily
BACKUP_SCHEDULE_TIME=02:15
BACKUP_RETENTION_DAYS=14
ALLOW_RESTORE_DRILL_IN_PRODUCTION=false
```

No hardcoded DB credentials in backup code.

---

## 3. Daily schedule (AC-22)

Registered in `routes/console.php`:

```bash
php artisan schedule:list
# ensure cron: * * * * * cd /path/to/labbi && php artisan schedule:run >> /dev/null 2>&1
```

Manual:

```bash
php artisan labbi:backup --trigger=manual --notes="pre-release"
# or Admin UI → النسخ الاحتياطي → تشغيل نسخة يدوية (permission: backups.manage)
```

---

## 4. Restore procedure (AC-23)

### Warnings
- **Destructive:** replaces current DB table contents with artifact data.
- Prefer restore onto a **dedicated test/staging database** first.
- Take a fresh backup of current prod before any emergency restore.

### UI path
1. Admin → النسخ الاحتياطي → select backup  
2. Acknowledge destructive checkbox  
3. Type `RESTORE`  
4. Submit (requires `backups.manage`)

### CLI restore drill

```bash
# On TEST database only:
php artisan labbi:restore-drill --force
```

Drill flow: create marker patient → backup → delete marker → restore → assert marker present.

### Programmatic / ops restore

Use Admin UI or a privileged tinker session calling `BackupService::restore($backup, $admin, confirmed: true)` after validating the artifact path exists.

---

## 5. Verification after restore

1. `curl -s http://HOST/health` → `status=ok`, `checks.database=ok`  
2. Login as admin  
3. Spot-check: patients, open order, approved result, BI dashboard cards non-zero if data expected  
4. Confirm audit shows `backups.restored`  

---

## 6. Failure handling

- Failed backups set `status=failed` + `error_summary` (no secrets) and log to `storage/logs/backup-*.log`  
- Investigate disk space, permissions, ZIP extension (`php-zip`)  
- Do not delete audit rows (append-only)

---

## 7. Evidence for reviewers

| Check | Command / test |
|---|---|
| AC-22 | `php artisan schedule:list` + `Phase5AcceptanceTest::test_ac22_*` |
| AC-23 | `php artisan labbi:restore-drill --force` + `test_ac23_*` |
