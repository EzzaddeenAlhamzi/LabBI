# LabBI — Project Progress Tracker

**Purpose:** Permanent execution tracker for LabBI.  
**Current phase:** Phase 1 — Core Platform (**in progress**)  
**Rule:** Do not mark requirements implemented until coded and verified.

---

## Overall Status

| Field | Value |
|---|---|
| Product | LabBI — نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال |
| Repository | Planning docs + Phase 1 Laravel application |
| Phase 0 | **Approved** — decisions locked in `DECISIONS.md` |
| Phase 1 | **In progress** |
| Phase 2+ | **Not started** — do not begin until Phase 1 user approval |
| Implementation code | Phase 1 Laravel app (this branch) |

---

## Phase Checklist

| Phase | Description | Status |
|---|---|---|
| P0 | Discovery & planning documentation | `approved` |
| P1 | Core platform | `in progress` |
| P2 | Laboratory workflow | `not started` |
| P3 | Communication & documents | `not started` |
| P4 | Intelligence (BI/comparison/TAT) | `not started` |
| P5 | Security & production | `not started` |

---

## Locked Decisions (Phase 0 → Phase 1 gate)

| Topic | Lock |
|---|---|
| Backend | Laravel |
| REV-05 | Admin only via `results.approve` permission |
| GAP-05 | No Receptionist role in V1 |
| GAP-07 / ANL-* | Future — manual entry V1 |

---

## Phase 0 Task Tracker

| Task | Status | Notes |
|---|---|---|
| Planning documentation set | done | Imported / updated on Phase 1 branch |
| User approval for Phase 1 | done | Proceeding with locked decisions |
| Decision locks (Laravel, REV-05, Receptionist, Analyzer) | done | `DECISIONS.md` |

---

## Phase 1 Task Tracker

| Task | Status | Notes |
|---|---|---|
| Laravel + MySQL skeleton | in progress | |
| Auth + Arabic RTL shell | pending | |
| RBAC + 4 roles seed (no receptionist) | pending | `results.approve` → Admin only |
| Users / Doctors / Lab staff / Departments | pending | |
| Patients + unique Medical ID | pending | |
| Test catalog + reference/critical ranges UI | pending | Not Critical Value Engine |
| Orders + Doctor Create Order | pending | |
| Sample reception & matching | pending | |
| Audit instrumentation | pending | |
| Automated + manual verification | pending | AC-01…AC-06, AC-21, AC-24 |
| Completion report | pending | `docs/PHASE1_COMPLETION_REPORT.md` |

---

## Requirements Implementation Status (summary)

- Phase 1 requirement rows remain `planning` / `in progress` until coded+verified.  
- Nothing marked `implemented` until tests pass.  
- Phase 2+ requirements remain untouched.

---

## Open Decision Gates (remaining)

1. WhatsApp provider (P3)  
2. PDF template (P3)  
3. Hosting preference (P5)  
4. Retention policy  
5. CAT-04 medical catalog sign-off (before prod critical alerts)

---

## Change Log (tracker)

| Date (UTC) | Entry |
|---|---|
| 2026-09-12 | Phase 0 planning docs created |
| 2026-09-12 | Phase 0 approved; decisions locked; Phase 1 started |
