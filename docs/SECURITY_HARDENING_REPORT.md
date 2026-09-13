# LabBI — Security Hardening Report (Phase 5)

**Status:** Implementer complete — pending independent A8 re-cert  
**Date (UTC):** 2026-09-13

---

## 1. Control map

| Control | Implementation | Notes |
|---|---|---|
| Authn | Session login + active-user middleware | Passwords `hashed` cast / bcrypt |
| Authz / RBAC | `EnsurePermission` on routes + policies in services | Deny by default |
| Sessions / cookies | `SESSION_*` env; HttpOnly default true; SameSite=lax; Secure via env in prod | See PRODUCTION_DEPLOYMENT |
| CSRF | Laravel web middleware | Meta csrf-token in layout |
| XSS | Blade `{{ }}` escaping; CSP baseline | Avoid `{!! !!}` for user content |
| SQLi | Eloquent / query builder only | No raw user SQL |
| Validation | Controller validation + service ValidationException | |
| Rate limit / brute force | `throttle:login` + `RateLimiter` in LoginController | Audits `auth.login_failed` / `auth.login_throttled` |
| Uploads | `AttachmentService` MIME allowlist + 5MB | Private `local` disk |
| PDF / attachments | Auth download routes; ACL in services | Audited |
| IDOR | Patient/doctor scoping in portals + 403 tests | |
| Errors | Arabic 403/404/419/429/500/503; no stacks when `APP_DEBUG=false` | |
| Headers | `SecurityHeaders` middleware | HSTS when HTTPS / `FORCE_HTTPS` |
| Trusted proxies | `TRUSTED_PROXIES` env (empty / CSV / explicit `*`) | Default empty — no spoofed `X-Forwarded-*` |
| Secrets | `.env` only; Settings UI shows non-secrets | |
| PHI in logs | Audit sanitizer redacts secrets; security channel avoids PHI dumps | |
| Encryption at rest | `Patient.national_id` → Laravel `encrypted` (AES-256-CBC via `APP_KEY`) | Passwords remain hashed (not reversible encrypt) |
| Append-only audit | Model blocks update/delete | |
| Result reject | `pending_review` only (same as approve/return) | Pre-prod M1 |
| Doctor patient ACL | Explicit relationship only — no unassigned claim | Pre-prod M2 |
| Role-change audit | `users.role_change` with old/new roles + permissions | Pre-prod M3 |
| Restore UI errors | Generic Arabic; `report($e)` server-side | Pre-prod M5 |

---

## 2. Encryption review

| Data | At rest | Why |
|---|---|---|
| Passwords | Adaptive hash (bcrypt) | Auth-06 — never reversible encrypt |
| `patients.national_id` | Framework encrypted cast (AES-256-CBC / APP_KEY) | Sensitive identifier; **not** used as search key |
| Medical file number, name, phone | Plain columns + ACL | Required for search/indexes (do not encrypt blindly) |
| Results / orders | Plain + ACL + audit | Clinical query performance |
| Attachments / PDF | Private disk (not under public/) | Auth download only |
| Backups | Private disk path under configured `BACKUP_DISK` | Restrict OS ACLs in prod |
| Host / volume | Recommend LUKS/cloud disk encryption | Ops responsibility |

**Keys:** `APP_KEY` and all provider secrets from environment only. Never commit real secrets.

---

## 3. HTTP security headers

Set by app middleware (also document reverse-proxy equivalents):

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN` (clickjacking)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` baseline (self + Google Fonts)
- `Permissions-Policy` (disable camera/mic/geo)
- `Strict-Transport-Security` when request is secure or `FORCE_HTTPS=true`

TLS termination and HTTP→HTTPS redirect: reverse proxy (see PRODUCTION_DEPLOYMENT.md).

**Trusted proxies:** set `TRUSTED_PROXIES` to the reverse-proxy/LB address(es). Do **not** leave `*` if clients can reach PHP-FPM directly. Empty/unset = ignore `X-Forwarded-*` (correct for local direct access).

---

## 4. Audit coverage (AUD-02+)

| Area | Actions (examples) |
|---|---|
| Auth | `auth.login`, `auth.logout`, `auth.login_failed`, `auth.login_throttled`, `auth.login_inactive` |
| Admin users | `users.create/update/activate`, `users.role_change` (old/new `role_codes` + `permission_codes`) |
| Patients / doctors / staff / catalog / ranges | create/update actions |
| Orders / samples / results / critical | Phase 1–2 instrumentation |
| Communications / PDF / requests | Phase 3 instrumentation |
| Backups / settings | `backups.*`, `settings.viewed` |
| Permission mutations | Role sync audited on user update; seeder-only grant UI still N/A |

---

## 5. A8 re-cert checklist (hooks)

- [ ] Confirm `APP_DEBUG=false` on staging/prod  
- [ ] Confirm Secure cookies + HTTPS redirect at proxy  
- [ ] Confirm `TRUSTED_PROXIES` matches deployment perimeter (not accidental `*`)  
- [ ] Attempt unauthorized `/admin/audit-logs`, `/admin/backups`, attachment URL guessing  
- [ ] Confirm audit detail view never shows password/token; role-change shows old/new roles  
- [ ] Confirm reject of approved/draft results is denied; pending_review reject allowed  
- [ ] Confirm doctor cannot claim/view/order unassigned or foreign patients  
- [ ] Confirm restore UI failure shows generic Arabic (no exception string)  
- [ ] Confirm restore requires `RESTORE` confirmation + `backups.manage`  
- [ ] Review log files for accidental PHI/secrets  

**Commands:** see `PRE_PRODUCTION_SECURITY_CLOSURE.md` and `PHASE5_ACCEPTANCE_REPORT.md`

**Pre-production Medium closure:** M1–M5 addressed on branch `cursor/pre-production-security-closure-9285`. Independent A8 must report Critical/High/Medium = 0 unresolved after re-review.
