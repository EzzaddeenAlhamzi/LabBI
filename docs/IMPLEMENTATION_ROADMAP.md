# LabBI — Implementation Roadmap

**Status:** Phase 1 in progress — Phase 0 decisions locked (see `DECISIONS.md`)  
Source §48 priorities aligned and expanded with agent parallelization.

---

## 1. Phase Overview

| Phase | Name | Goal | Exit criteria |
|---|---|---|---|
| **P0** | Discovery & Planning | Requirements, architecture, docs | This doc set + user approval |
| **P1** | Core Platform | Auth, RBAC, users, patients, catalog, ranges, doctors/staff, orders, sample receive | AC-01…AC-06, AC-21, AC-24 (partial) |
| **P2** | Laboratory Workflow | Results, attachments, critical engine, unified workflow, review/approval | AC-07…AC-13, AC-18 (partial) |
| **P3** | Communication & Documents | Notification center, email, WhatsApp adapter, PDF, patient readiness | AC-14…AC-17 |
| **P4** | Intelligence | Admin BI, comparison, critical dashboard, TAT, operational reports | AC-19, AC-20 |
| **P5** | Security & Production | Audit UI, backups/restore, hardening, full browser QA, deploy docs | AC-22, AC-23, AC-25, NFR security |

**Out of current roadmap unless re-scoped:** inventory, accounting, diagnostic AI, mobile app, analyzer integration (default Future).

---

## 2. Phase 0 Deliverables (this PR)

All files under `/workspace/docs/` listed in `PROJECT_OVERVIEW.md` + source materials.  
No application code.

---

## 3. Phase 1 — Core (after approval)

**Build order inside P1:**

1. Project skeleton (PHP framework decision) + MySQL + Arabic layout shell  
2. Auth + sessions + password hashing  
3. Roles/permissions seed + policies  
4. Users admin CRUD  
5. Departments, doctors, lab staff  
6. Patients + unique medical ID + search  
7. Test catalog + reference/critical ranges UI  
8. Orders + items + submit to lab  
9. Sample receive/match  
10. Wireframes for critical screens (GAP-13)  

**REV-05 locked:** Admin approval via `results.approve` permission (D-008).  
**Do not start P2** until Phase 1 exit criteria are met and user approves; CAT-04 medical catalog sign-off still required before production critical alerts.

---

## 4. Phase 2 — Laboratory Workflow

1. Result entry + draft/submit validation  
2. Critical Value Engine (unit-tested extensively)  
3. Critical alert records + lab/doctor visibility  
4. Review/approve/return  
5. Attachments  
6. Status machine completion  
7. Patient archive tabs (partial)  

---

## 5. Phase 3 — Communication

1. Notification center  
2. Email doctor with PDF/link  
3. WhatsApp adapter (provider decided)  
4. PDF generator with approved template  
5. Patient portal notifications + downloads  
6. Patient interpretation/re-test requests  

---

## 6. Phase 4 — Intelligence

1. Admin BI cards  
2. Operational reports  
3. Historical comparison + trends  
4. Critical cases dashboard + TAT metrics  
5. Optional charts (not mandatory for MVP per source)  

---

## 7. Phase 5 — Production Readiness

1. Audit log UI + coverage completion  
2. Daily backup + restore drill  
3. Security hardening (HTTPS, AES-256 fields, rate limits, CSRF, etc.)  
4. Cross-browser QA  
5. Deployment guides (local + cloud)  
6. Performance smoke for search/BI  

---

## 8. Specialized Agent Structure (non-conflicting)

Use separate agents/owners with **hard module ownership** to avoid merge conflicts.

| Agent ID | Name | Owns (write) | May read | Must not touch |
|---|---|---|---|---|
| A0 | Coordinator / Planner | `docs/`, roadmap, decisions | All | Application feature code |
| A1 | Platform & Auth | M01–M03, app skeleton, middleware | Shared contracts | Clinical engines, BI UI |
| A2 | Master Data | M04–M09 patients/doctors/staff/catalog/ranges | Auth contracts | Results/critical/notifications |
| A3 | Orders & Samples | M10–M12 | Catalog/patients APIs | Critical engine internals, PDF/email |
| A4 | Results & Critical | M13–M16 | Ranges, workflow API | Auth, BI aggregations, WhatsApp provider |
| A5 | Communications | M17–M21 notifications/email/WhatsApp/PDF/requests | Result approval events | DB catalog clinical values, auth |
| A6 | Experience / Portals UI | Portal pages consuming APIs | All API contracts | Domain engine algorithms |
| A7 | BI & Reporting | M23–M24 | Read models | Write paths for results |
| A8 | Security & Ops | M25–M27, hardening, backup | All | Feature product UX except security screens |
| A9 | QA | Tests, AC suite, fixtures | All | Production secrets; avoid owning feature impl |

### Conflict avoidance rules

1. One writable module owner at a time; PRs scoped to agent ownership  
2. Shared DTOs/events live in `contracts/` owned by Coordinator or Platform with review  
3. Critical Engine tests owned by A4; A9 adds black-box AC only  
4. UI agents never duplicate threshold logic client-side  
5. Schema migrations: single sequenced queue; A2/A3/A4 propose, Platform merges order  

---

## 9. Parallelization Strategy

```
After P0 approval:
  Track T1 (A1): skeleton + auth + RBAC          ─┐
  Track T2 (A2): patients + catalog + ranges     ─┼─ merge to integrate orders
  Track T3 (A6): Arabic shell + login/layout     ─┘

P1 integration gate → Orders/Samples (A3) while A6 builds doctor/lab list screens

P2:
  A4 critical+results   ||   A6 lab result UI   ||   A8 audit instrumentation

P3:
  A5 email/PDF/WhatsApp ||   A6 notification UIs ||   A9 AC-14..17 automation

P4:
  A7 BI + comparison    ||   A6 dashboards

P5:
  A8 backup/security    ||   A9 full regression ||   A0 release docs
```

**Max useful parallel coding agents:** 3–4 concurrent on separate modules after contracts exist.  
**Do not parallelize** Critical Engine + Workflow status enums until WFL decisions locked.

---

## 10. Dependency Gates

| Gate | Blocks | Status |
|---|---|---|
| Framework (Laravel) | P1 skeleton | **Locked** — Laravel (D-003) |
| Approver authority | P2 approve API/UI | **Locked** — Admin via `results.approve` (D-008 / REV-05) |
| WhatsApp provider | P3 WA | Open — interface + mock |
| PDF template | P3 PDF polish | Open — minimal Arabic stub until assets |
| Analyzer in/out | Any device ingestion | **Locked Future** — manual only V1 (D-006) |
| Receptionist role | P1 roles seed | **Locked** — no Receptionist (D-009) |
| Hosting target | P5 deploy | Open — portable Linux |
| CAT-04 medical ranges | Prod critical alerts | Open — medical sign-off |

---

## 11. Definition of Done (product)

All AC-01…AC-25 pass on staging; Arabic UI; RBAC denials verified; backup restore drilled; no out-of-scope modules shipped.
