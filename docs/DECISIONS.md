# LabBI — Decisions Log

**Status:** Phase 0 — decisions proposed; several await user confirmation.  
**Rule:** No irreversible stack lock without rationale + open questions documented here.

---

## Decision Record Format

Each entry: **ID · Status · Decision · Rationale · Open questions · Reversible?**

Statuses: `proposed` | `accepted` | `rejected` | `deferred`

---

## D-001 — Product name & scope baseline

- **Status:** proposed (aligns with source title)  
- **Decision:** Product name **LabBI**; full name نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال; web-only LIMS+BI circulation platform  
- **Rationale:** Matches graduation-project title preferred in source §1  
- **Open questions:** Confirm English brand spelling LabBI vs alternate  
- **Reversible?** Yes (branding)

---

## D-002 — Out of scope for v1

- **Status:** proposed (per source §5 / §45)  
- **Decision:** Exclude inventory, complex accounting, automatic diagnosis/AI diagnosis, native mobile app  
- **Rationale:** Explicit exclusions in analysis  
- **Open questions:** None unless user expands scope  
- **Reversible?** Yes via formal scope change

---

## D-003 — Backend stack (PHP) & framework

- **Status:** proposed — **not locked**  
- **Decision (recommendation):** PHP 8.x + **Laravel** + Eloquent ORM + queues + scheduler; MySQL 8  
- **Rationale:** Source mandates PHP + MySQL; Laravel is compatible (source §42 says Laravel not required but fully compatible), accelerates RBAC, validation, jobs (email/WhatsApp/PDF/backup), and maintainability  
- **Open questions:** Accept Laravel, prefer another PHP framework, or structured plain PHP?  
- **Reversible?** Costly after P1 skeleton — decide before coding

---

## D-004 — Frontend approach

- **Status:** proposed — **not locked**  
- **Decision (recommendation):** Server-rendered Blade (or Inertia) with progressive JS; Arabic RTL-first CSS; avoid heavy unnecessary SPA for MVP  
- **Rationale:** Faster AC delivery for four portals; TECH-01 satisfied (HTML/CSS/JS)  
- **Open questions:** Blade vs Inertia/Vue vs separate React SPA?  
- **Reversible?** Moderately costly after P1 UI

---

## D-005 — Unified order workflow statuses

- **Status:** proposed  
- **Decision:** Use unified enum: `requested` → `sample_received` → `in_analysis` → `pending_review` → `ready` → `completed` → `archived` + `deferred` + `rejected`  
- **Rationale:** Resolves contradictory status sets in source §21 / GAP-04  
- **Open questions:** Exact Arabic labels for UI; whether `ready` and `completed` both needed  
- **Reversible?** Early yes; hard after production data

---

## D-006 — Manual result entry vs analyzer integration

- **Status:** proposed default  
- **Decision:** **Option A — Manual entry only** for first delivery; analyzer integration = Future adapter  
- **Rationale:** Source SWOT marks analyzer as future; no device protocol details (GAP-07 / ANL-*)  
- **Open questions:** User insists on Option B now? If yes, provide device questionnaire  
- **Reversible?** Yes if adapter interface reserved

---

## D-007 — Doctor can create orders

- **Status:** proposed  
- **Decision:** Doctor portal includes Create Test Order (ORD-11)  
- **Rationale:** Lab receives orders from doctors; GAP-01  
- **Open questions:** Can admin/receptionist also create on behalf of doctor?  
- **Reversible?** Easy

---

## D-008 — Result approval authority

- **Status:** deferred — **user decision required**  
- **Decision (recommendation):** Four-eyes — approver is lab technician **different from** enterer, or new Lab Supervisor role  
- **Rationale:** Medical safety; source requires review but unnamed approver (GAP-03)  
- **Open questions:** Choose A/B/C/D from `ROLES_AND_PERMISSIONS.md` §6  
- **Reversible?** Until P2 ships

---

## D-009 — Receptionist role

- **Status:** proposed default  
- **Decision:** No fifth role in v1; admin/lab perform reception  
- **Rationale:** Not a formal functional role in requirements (GAP-05)  
- **Open questions:** Add `receptionist`?  
- **Reversible?** Easy early

---

## D-010 — Patient–doctor communication

- **Status:** proposed  
- **Decision:** Structured **Patient Request** (interpretation / re-test), not live chat  
- **Rationale:** Source ambiguous (GAP-08); requests match portal screen UI-P-07  
- **Open questions:** Need real-time chat?  
- **Reversible?** Yes

---

## D-011 — WhatsApp provider

- **Status:** deferred  
- **Decision:** Define `WhatsAppSender` interface + mock; choose provider later (Meta Cloud API, Twilio, local aggregator, etc.)  
- **Rationale:** Provider unspecified (GAP-06); avoid premature lock  
- **Open questions:** Which provider/account for production?  
- **Reversible?** Yes if interface held

---

## D-012 — Password hashing algorithm

- **Status:** proposed  
- **Decision:** bcrypt or Argon2id for password storage; reject raw SHA-256-only password storage  
- **Rationale:** Source mentions Bcrypt & SHA-256; analysis correctly prefers adaptive hashing  
- **Open questions:** bcrypt vs Argon2id preference  
- **Reversible?** Migration possible but awkward

---

## D-013 — Soft delete for patients

- **Status:** proposed  
- **Decision:** Soft delete medical records; no hard delete in normal UI  
- **Rationale:** Source mentions delete; clinical safety prefers soft delete  
- **Open questions:** Legal hard-delete/right-to-erasure process?  
- **Reversible?** Policy-level

---

## D-014 — BI MVP without mandatory charts

- **Status:** proposed  
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
- **Decision:** Block polished PDF on stakeholder template (logo, signature, QR, language)  
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

- **Status:** proposed add-on  
- **Decision:** Include email-based password reset in P1/P5 even though absent from source  
- **Rationale:** GAP-11; production usability/security  
- **Open questions:** Email vs SMS OTP?  
- **Reversible?** Easy

---

## D-019 — Agent ownership model

- **Status:** proposed  
- **Decision:** Use specialized agents A0–A9 with non-overlapping module write ownership (`IMPLEMENTATION_ROADMAP.md`)  
- **Rationale:** Parallelization without conflicting responsibilities  
- **Open questions:** Team size / whether humans map 1:1 to agents  
- **Reversible?** Yes

---

## D-020 — Cache/Redis

- **Status:** deferred  
- **Decision:** Not required for P1; introduce if session/queue/cache needs demand  
- **Rationale:** Avoid extra moving parts before necessity  
- **Open questions:** Use database queues initially?  
- **Reversible?** Easy
