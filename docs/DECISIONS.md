# LabBI — Decisions Log

**Status:** Phase 1 — core stack and governance decisions locked for V1.  
**Rule:** Locked items below must not be reverted without an explicit new decision entry.

---

## Decision Record Format

Each entry: **ID · Status · Decision · Rationale · Open questions · Reversible?**

Statuses: `proposed` | `accepted` | `rejected` | `deferred` | `locked`

---

## D-001 — Product name & scope baseline

- **Status:** accepted  
- **Decision:** Product name **LabBI**; full name نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال; web-only LIMS+BI circulation platform  
- **Rationale:** Matches graduation-project title preferred in source §1  
- **Open questions:** Confirm English brand spelling LabBI vs alternate  
- **Reversible?** Yes (branding)

---

## D-002 — Out of scope for v1

- **Status:** locked  
- **Decision:** Exclude inventory, complex accounting, automatic diagnosis/AI diagnosis, native mobile app  
- **Rationale:** Explicit exclusions in analysis  
- **Open questions:** None unless user expands scope  
- **Reversible?** Yes via formal scope change

---

## D-003 — Backend stack (PHP) & framework

- **Status:** locked  
- **Decision:** PHP 8.x + **Laravel** + Eloquent ORM + queues + scheduler; MySQL 8 / MariaDB-compatible  
- **Rationale:** Source mandates PHP + MySQL; Laravel accelerates RBAC, validation, jobs, and maintainability. Locked for Phase 1 before coding.  
- **Open questions:** None for V1  
- **Reversible?** Costly after P1 skeleton — do not change mid-phase

---

## D-004 — Frontend approach

- **Status:** locked (P1)  
- **Decision:** Server-rendered Blade with progressive JS; Arabic RTL-first CSS; no heavy SPA for MVP  
- **Rationale:** Faster AC delivery for four portals; TECH-01 satisfied (HTML/CSS/JS)  
- **Open questions:** Inertia/Vue later if UX demands it  
- **Reversible?** Moderately costly after P1 UI

---

## D-005 — Unified order workflow statuses

- **Status:** accepted  
- **Decision:** Use unified enum: `requested` → `sample_received` → `in_analysis` → `pending_review` → `ready` → `completed` → `archived` + `deferred` + `rejected`  
- **Rationale:** Resolves contradictory status sets in source §21 / GAP-04  
- **Open questions:** Exact Arabic labels for UI polish  
- **Reversible?** Early yes; hard after production data

---

## D-006 — Manual result entry vs analyzer integration

- **Status:** locked (V1) — resolves **GAP-07** and **ANL-***  
- **Decision:** **Manual entry only** for V1; analyzer/device integration deferred to **Future**. Keep architecture adapter-ready (HL7/ASTM/vendor ingestion port stubs/comments only). Do not implement real analyzer integration in P1–P5 unless re-scoped.  
- **Rationale:** Source SWOT marks analyzer as future; no device protocol details  
- **Open questions:** Device questionnaire required before any Future work  
- **Reversible?** Yes if adapter interface reserved

---

## D-007 — Doctor can create orders

- **Status:** locked  
- **Decision:** Doctor portal includes Create Test Order (ORD-11). Admin may also create on behalf of a doctor.  
- **Rationale:** Lab receives orders from doctors; GAP-01  
- **Open questions:** None for V1 (no Receptionist)  
- **Reversible?** Easy

---

## D-008 — Result approval authority

- **Status:** locked — resolves **REV-05** as **Admin approval**  
- **Decision:** V1 approval authority is **Admin only**, enforced via RBAC permission `results.approve` (and related return permission as needed). **Do not** hardcode role-name checks in application logic. Seed the approval permission initially assigned **only** to the Admin role. Lab Technician may enter/submit results in P2 but cannot approve unless later granted the permission.  
- **Rationale:** User lock for Phase 1 governance; permission-based design allows future reassignment without code changes  
- **Open questions:** Whether to add Lab Supervisor role in a later phase  
- **Reversible?** Permission reassignment easy; changing model after P2 UI ships needs migration of grants

---

## D-009 — Receptionist role

- **Status:** locked — resolves **GAP-05**  
- **Decision:** **No Receptionist role in V1.** Do not create or seed a receptionist role. Patient registration and sample reception are performed by Admin and/or Laboratory Technician.  
- **Rationale:** Not a formal functional role in requirements  
- **Open questions:** Add later if ops demand it  
- **Reversible?** Easy early

---

## D-010 — Patient–doctor communication

- **Status:** accepted  
- **Decision:** Structured **Patient Request** (interpretation / re-test), not live chat  
- **Rationale:** Source ambiguous (GAP-08); requests match portal screen UI-P-07  
- **Open questions:** Need real-time chat?  
- **Reversible?** Yes

---

## D-011 — WhatsApp provider

- **Status:** deferred for production provider; **interface implemented in P3**  
- **Decision:** `App\Contracts\WhatsAppGateway` + `MockWhatsAppGateway` (no Twilio/Meta/UltraMsg in domain). Production provider still TBD.  
- **Rationale:** Provider unspecified (GAP-06)  
- **Open questions:** Which provider/account for production?  
- **Reversible?** Yes if interface held

---

## D-012 — Password hashing algorithm

- **Status:** locked  
- **Decision:** Laravel bcrypt (or Argon2id if configured); reject raw SHA-256-only password storage  
- **Rationale:** Adaptive hashing for passwords  
- **Open questions:** None  
- **Reversible?** Migration possible but awkward

---

## D-013 — Soft delete for patients

- **Status:** accepted  
- **Decision:** Soft delete medical records; no hard delete in normal UI  
- **Rationale:** Clinical safety  
- **Open questions:** Legal hard-delete/right-to-erasure process?  
- **Reversible?** Policy-level

---

## D-014 — BI MVP without mandatory charts

- **Status:** accepted  
- **Decision:** MVP BI = cards, tables, color indicators; charts optional later  
- **Rationale:** Source clarifies analysis need not require graphs  
- **Open questions:** Stakeholders want charts in P4 anyway?  
- **Reversible?** Easy

---

## D-015 — Hosting model

- **Status:** deferred  
- **Decision:** Design portable Linux/PHP/MySQL deploy for both local server and cloud  
- **Rationale:** Source §43 leaves choice open (HOST-*)  
- **Open questions:** Where will production run? Data residency requirements?  
- **Reversible?** Until P5

---

## D-016 — PDF template

- **Status:** deferred for polished template; **P3 stub shipped**  
- **Decision:** Functional Arabic RTL DomPDF stub with reserved slots for logo/signature/QR/bilingual; polished template blocked on stakeholder assets (GAP-09)  
- **Rationale:** GAP-09  
- **Open questions:** Provide template assets  
- **Reversible?** Template swap later OK

---

## D-017 — Data retention

- **Status:** deferred  
- **Decision:** None yet — assume retain clinical data indefinitely until policy exists  
- **Rationale:** GAP-10  
- **Open questions:** Retention periods for results, files, backups, audits  
- **Reversible?** Policy

---

## D-018 — Password recovery

- **Status:** accepted (email reset available in Laravel auth)  
- **Decision:** Include email-based password reset scaffolding in P1 even though absent from source  
- **Rationale:** GAP-11; production usability/security  
- **Open questions:** Email vs SMS OTP for production?  
- **Reversible?** Easy

---

## D-019 — Agent ownership model

- **Status:** accepted  
- **Decision:** Use specialized agents A0–A9 with non-overlapping module write ownership (`IMPLEMENTATION_ROADMAP.md`). Phase 1 may ship as a coherent single-branch greenfield delivery.  
- **Rationale:** Parallelization without conflicting responsibilities  
- **Open questions:** Team size  
- **Reversible?** Yes

---

## D-020 — Cache/Redis

- **Status:** deferred  
- **Decision:** Not required for P1; database sessions/queues initially  
- **Rationale:** Avoid extra moving parts before necessity  
- **Open questions:** Use Redis when scale demands  
- **Reversible?** Easy

---

## D-021 — Phase 4 TAT timestamps & trend policy

- **Status:** accepted  
- **Decision:**  
  - TAT uses real columns only: `ordered_at`, `samples.received_at`, `analysis_started_at`, `results.submitted_at`/`entered_at`, `results.approved_at`, `completed_at`, `critical_alerts.discovered_at`/`notified_at`/`acknowledged_at`.  
  - Missing timestamps report limitations — never invent.  
  - Critical dashboard consumes stored Phase 2 `critical_alerts` only (no CriticalValueEngine duplication).  
  - Trends are Rising/Falling/Stable with 5% relative threshold; incompatible units → safe non-trend; language is non-diagnostic.  
- **Rationale:** Align BI with actual schema (`discovered_at` not `detected_at`); AC-19/AC-20; BI-05  
- **Open questions:** Stakeholder confirmation of 5% stable threshold  
- **Reversible?** Formula threshold yes; column names locked to schema

---

## D-022 — Phase 4 Admin BI ACL + reporting windows (A8 Medium)

- **Status:** accepted  
- **Decision:**  
  - **Authorization matrix (Admin BI):**  

    | Surface | Route | Required permission(s) | Lab tech | Doctor | Patient |
    |---|---|---|---|---|---|
    | Admin home BI cards/TAT | `GET /admin` | `bi.view` (nested; outer group alone insufficient) | 403 | 403 | 403 |
    | BI dashboard | `GET /admin/bi` | `bi.view` | 403 | 403 | 403 |
    | Operational reports | `GET /admin/reports/operational` | `reports.operational` **OR** `bi.view` | 403 | 403 | 403 |
    | Critical cases BI | `GET /admin/critical-cases` | `bi.view` **only** (not `critical.view_all`) | 403 | 403 | 403 |
    | Lab critical ops | `GET /lab/critical` | `critical.view_all` | ✓ | — | — |
    | Doctor comparison | `GET /doctor/comparison` | `comparison.use` + patient-scoped service ACL | 403 | own patients | 403 |

  - Prefer explicit BI permissions over role ORs. `critical.view_all` / `patients.view_all` must **not** open Admin BI.  
  - **Reporting window:** when `date_from`/`date_to` omitted on BI/operational/critical reporting filters → default **last 30 inclusive days**; max span **366 inclusive days** (ValidationException). Doctor comparison does **not** force the 30-day default (patient-scoped) but still rejects excessive explicit pairs.  
  - **TAT aggregates:** summary averages use SQL (`AVG` of the same timestamp formulas as `TATService`); detail rows stay paginated; no fetch-all-then-calc for metrics; no Redis.  
- **Rationale:** Close A8 M1/M2 privilege creep and M3/M4 unbounded hydrate / N+1.  
- **Open questions:** None for Phase 4 merge.  
- **Reversible?** Window lengths tunable; ACL tightening is intentional.

---

## D-023 — Phase 5 security/ops + backup defaults

- **Status:** accepted  
- **Decision:**  
  - Encrypt `patients.national_id` via Laravel encrypted cast (AES-256-CBC / `APP_KEY`); do **not** encrypt searchable keys (medical file number, name, phone) in V1.  
  - Passwords remain adaptive hashes (never reversible encryption).  
  - Daily backup via scheduler `labbi:backup`; portable ZIP (DB JSON + private files) on `BACKUP_DISK`.  
  - Restore requires `backups.manage` + explicit `RESTORE` confirmation; restore drill blocked in production unless `ALLOW_RESTORE_DRILL_IN_PRODUCTION=true`.  
  - Audit logs append-only; Admin Audit UI redacts secrets / national_id.  
  - Security headers middleware + reverse-proxy TLS; `APP_DEBUG=false` mandatory in production.  
  - System Settings UI shows non-secret config only.  
- **Rationale:** Close AC-22/23 and NFR security/backup without breaking clinical search indexes.  
- **Open questions:** Retention length (GAP-10); hosting provider preference.  
- **Reversible?** Field encryption set expandable carefully; backup format versioned (`database.json` v1).

---

## D-024 — Pre-production A8 Medium closure (M1–M5)

- **Status:** accepted  
- **Decision:**  
  - **M1:** Result reject/return/approve only from `pending_review` (server-enforced). No reject-after-approve kill switch in V1.  
  - **M2:** Doctors cannot claim unassigned patients via order create; Admin assigns `primary_doctor_id`. Authorized = primary doctor **or** existing order relationship.  
  - **M3:** User role sync audits `users.role_change` with old/new `role_codes` and `permission_codes` (no secrets).  
  - **M4:** `TRUSTED_PROXIES` env-driven; default empty (do not trust forwarded headers); `*` only when perimeter-locked.  
  - **M5:** Restore UI returns generic Arabic errors; technical detail logged server-side only.  
- **Rationale:** Close A8 Medium integrity/IDOR/audit/proxy/error-exposure gaps without adding features.  
- **Open questions:** CAT-04 clinical catalog remains a separate gate.  
- **Reversible?** Proxy list tunable; claim policy intentional hardening.

---

## Locked summary (Phase 1 gate)

| Topic | Lock |
|---|---|
| Backend | **Laravel** |
| REV-05 / approval | **Admin only**, via **`results.approve` permission** (not role-name hardcoding) |
| GAP-05 / Receptionist | **No** Receptionist role in V1 |
| GAP-07 / ANL-* | **Future** — V1 manual entry; adapter-ready only |
