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

- **Status:** deferred (blocks P3 only)  
- **Decision:** Define `WhatsAppSender` interface + mock; choose provider later  
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

- **Status:** deferred  
- **Decision:** Block polished PDF on stakeholder template  
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

## Locked summary (Phase 1 gate)

| Topic | Lock |
|---|---|
| Backend | **Laravel** |
| REV-05 / approval | **Admin only**, via **`results.approve` permission** (not role-name hardcoding) |
| GAP-05 / Receptionist | **No** Receptionist role in V1 |
| GAP-07 / ANL-* | **Future** — V1 manual entry; adapter-ready only |
