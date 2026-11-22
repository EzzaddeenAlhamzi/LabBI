# LabBI — Demo Seeding Guide

**Audience:** developers / QA on local or staging  
**Entry point:** `php artisan db:seed --class=LabBIDemoSeeder`  
**Safety:** refuses `APP_ENV=production`. Not wired into `DatabaseSeeder`.

---

## Profiles

| Env | Patients | Doctors | Lab staff | Tests | Orders | Items (approx) |
|-----|----------|---------|-----------|-------|--------|----------------|
| `LABBI_DEMO_PROFILE=small` | 50 | 10 | 5 | ≥2 | ~100 | ~175 |
| `LABBI_DEMO_PROFILE=medium` | 1000 | 30 | 15 | ≥8 | ~3000 | ~5000 |
| `LABBI_DEMO_PROFILE=large` | 5000 | 100 | 40 | ≥20 | 20000 | ~35000 |

Determinism: `LABBI_DEMO_SEED` (default `42`). Chunk size: `LABBI_DEMO_CHUNK` (default `250`).

---

## Prerequisites

1. Non-production `APP_ENV` (`local`, `staging`, or `testing`).
2. Migrated schema (`php artisan migrate`) — **do not** `migrate:fresh` against shared/prod data.
3. Optional: run default seeder first for RBAC; `LabBIDemoSeeder` bootstraps `DatabaseSeeder` if `admin@labbi.local` is missing.

---

## Commands

```bash
# .env
LABBI_DEMO_PROFILE=small
LABBI_DEMO_SEED=42

# Ensure baseline RBAC + light demos (safe, idempotent)
php artisan db:seed

# Volume demo (opt-in)
php artisan db:seed --class=LabBIDemoSeeder
```

PowerShell (Windows path `F:\LabBI` after PO approval):

```powershell
cd F:\LabBI
git fetch origin
git checkout cursor/demo-seeding-system-ecb4   # after branch is pushed
copy .env.example .env   # if needed; set LABBI_DEMO_* 
php artisan migrate
php artisan db:seed
$env:LABBI_DEMO_PROFILE="small"; $env:LABBI_DEMO_SEED="42"
php artisan db:seed --class=LabBIDemoSeeder
```

---

## What is preserved

| Email | Role | Password |
|-------|------|----------|
| `admin@labbi.local` | admin | `password` |
| `doctor@labbi.local` | doctor | `password` |
| `lab@labbi.local` | lab_technician | `password` |
| `patient@labbi.local` | patient | `password` |

Also preserved: `MF-000001`, `HB`/`GLU`, `DOC-1001`, admin-only approve/return, no receptionist.

Synthetic identities use `demo.*.DEMO{seed}-{profile}.*@labbi.local`, `MF-DEMO…`, `ORD-DEMO…`, `SMP-DEMO…`.

---

## Behaviour

- Idempotent within a namespace: re-run purges prior synthetic rows for the same `seed+profile`, then recreates.
- Workflow mix: requested → sample_received → in_analysis → pending_review → ready/archived (+ deferred/rejected/returned).
- Classifications via `CriticalValueEngine` (ranges **demo-only until CAT-04**).
- `Mail::fake()`; WhatsApp uses mock driver — no real email/SMS/WhatsApp.
- Real PDF files on local disk for a capped subset (`labbi.demo.pdf_cap.*`).
- Progress lines + completion summary printed to the console.

---

## What NOT to do

- Do not call `LabBIDemoSeeder` from `DatabaseSeeder`.
- Do not run in production.
- Do not `migrate:fresh` / truncate production tables to “make room”.
- Do not treat demo reference ranges as clinical authority (CAT-04).
