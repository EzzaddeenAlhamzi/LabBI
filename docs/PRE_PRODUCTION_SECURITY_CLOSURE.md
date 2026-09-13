# LabBI — Pre-Production Security Closure (A8 Mediums M1–M5)

**Status:** Implementer complete — awaiting independent A8/A9 re-review  
**Branch:** `cursor/pre-production-security-closure-9285`  
**Base:** `origin/main` (Phase 5 merged, PR #13 / #14)  
**Date (UTC):** 2026-09-13  

**Does not** claim TECHNICAL PRODUCTION RELEASE READY. Coordinator consolidates after A8/A9 re-review.  
**A8 re-review target:** Critical / High / Medium = **0 unresolved**.  
**CAT-04** remains a clinical gate (medical catalog sign-off) — not closed by this PR.

---

## Scope

Security-only closure of five A8 Medium findings. No new business features. Approved workflows preserved except where the Medium required hardening (reject state guard; no open doctor claim of unassigned patients).

---

## Findings closed

| ID | Root cause | Fix |
|---|---|---|
| **M1** | `ResultReviewService::reject()` had no `pending_review` status guard — approved (and other) results could be rejected | Same guard as approve/return: reject only from `pending_review`; DomainException otherwise. Order reject still uses `OrderStatus::canTransition`. |
| **M2** | Doctor order `store` allowed claiming patients with `primary_doctor_id === null` by ID | Removed open claim. `DoctorPatientAccess` requires primary doctor match **or** prior order relationship. Admin assigns `primary_doctor_id`. Unauthorized claim/view/order → **403**. |
| **M3** | `UserController::update` synced `role_id` without auditing role/permission delta | Audit `users.role_change` (or `users.update`) with `role_codes` + `permission_codes` old/new, actor, target, timestamp. No secrets. |
| **M4** | Hard-coded `trustProxies(at: '*')` enabled spoofed `X-Forwarded-*` if app reachable without an edge | Env-driven `TRUSTED_PROXIES`: empty = trust none; CSV IPs/CIDRs = explicit; `*` only when perimeter-locked. |
| **M5** | Backup restore UI returned `$e->getMessage()` | Generic Arabic error; `report($e)` server-side. No paths/commands/stacks in browser. |

---

## Result review transition matrix (server-enforced)

| From (result) | approve | return | reject |
|---|---|---|---|
| `pending_review` | ✓ | ✓ | ✓ |
| `draft` | ✗ | ✗ | ✗ |
| `submitted` | ✗ | ✗ | ✗ |
| `approved` | ✗ | ✗ | ✗ |
| `returned` | ✗ | ✗ | ✗ |
| `rejected` | ✗ | ✗ | ✗ |

### Order-level reject (unchanged `OrderStatus`)

| From (order) | → `rejected` |
|---|---|
| `requested` / `sample_received` / `in_analysis` / `pending_review` | ✓ |
| `ready` / `completed` / `archived` | ✗ |
| `deferred` | ✗ (returns to workflow; not reject) |

---

## Doctor↔patient authorization

Authorized only when:

1. `patient.primary_doctor_id === doctor.id`, **or**
2. Doctor already has at least one order for that patient.

Unassigned patients (`primary_doctor_id = null`) must be assigned by **Admin** before a doctor can order/view. No self-claim via order create.

---

## Trusted proxies / reverse proxy

| Env | Behavior |
|---|---|
| unset / empty | Do **not** trust `X-Forwarded-*` (safe default for direct/local access) |
| `10.0.0.1,10.0.0.0/8` | Trust only listed IPs/CIDRs |
| `*` | Trust all — **only** when PHP is unreachable except via a locked reverse proxy |

See `PRODUCTION_DEPLOYMENT.md` § Reverse proxy.

---

## Verification (this suite)

```text
php artisan test
# Tests: 125 passed (815 assertions)
# Failures: 0 | Skipped: 0
```

New: `tests/Feature/PreProductionSecurityClosureTest.php` (M1–M5).

### Commands for independent A8 / A9 re-runs

```bash
# Full regression
php artisan test

# Security closure only
php artisan test --filter=PreProductionSecurityClosureTest

# Phase 5 + RBAC/IDOR baselines
php artisan test --filter=Phase5AcceptanceTest
php artisan test --filter=Phase5A9QaGapTest
php artisan test --filter=Phase1AcceptanceTest
php artisan test --filter=Phase2AcceptanceTest

# Backup / restore smoke
php artisan labbi:backup --trigger=manual --notes="pre-prod security smoke"
php artisan labbi:restore-drill --force   # staging only
php artisan schedule:list

# Health (no secrets)
curl -s http://127.0.0.1:8000/health
```

---

## Related docs

- `SECURITY_HARDENING_REPORT.md` — control map updated  
- `FINAL_SYSTEM_AUDIT.md` — closure note  
- `PROJECT_PROGRESS.md` — tracker  
- `REQUIREMENTS_MATRIX.md` — AUD-02 / NFR-SEC notes  
- `DECISIONS.md` — D-024  
- `PRODUCTION_DEPLOYMENT.md` — trusted proxies  

---

## Residual / outside this PR

- **CAT-04** clinical catalog gate — still open  
- A8 Lows (CSP `unsafe-inline`, health `env` string, etc.) — not in this Medium closure  
- WhatsApp production provider, PDF polish, GAP-10 retention — unchanged  
