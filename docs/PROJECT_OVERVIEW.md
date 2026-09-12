# LabBI — Project Overview

**Status:** Phase 0 — Planning only (not implemented)  
**Primary source:** `docs/source/labbi-requirements-extracted.md` (from Arabic graduation-project analysis of the Azhelha / LabBI platform)  
**Working name:** LabBI  
**Full Arabic name:** نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال  
**English equivalent:** Laboratory Test Management & Circulation System powered by Business Intelligence

---

## 1. What LabBI Is

LabBI is a **web-based** platform that digitizes the full laboratory test lifecycle by connecting:

| Stakeholder (Arabic) | English | Role in system |
|---|---|---|
| إدارة المختبر | Laboratory Administration | Full system, users, BI, reports |
| فني المختبر | Laboratory Technician | Samples, result entry, review |
| الأطباء | Doctors | Order tests, review results, history |
| المرضى | Patients | View own results, notifications, PDF |

It is **not** only a result-registration app. It must cover the **complete circulation of the laboratory result**: patient registration → order → sample → analysis/result → critical-value detection → approval → notify doctor & patient → PDF → archive → BI.

---

## 2. Problem Statement (9 problems from source)

1. Manual work; no unified electronic system  
2. Lost/damaged paper reports; transcription errors  
3. No automated critical medical value detection  
4. Slow delivery of results to doctor and patient  
5. Weak medical history archive; hard to compare old vs new results  
6. Weak administrative monitoring of lab performance  
7. Weak permissions, security, and encryption  
8. Inability to archive PDFs, images, and complex reports  
9. Higher operational cost/time from paper workflows  

---

## 3. Goals

- Unified electronic system  
- Unique medical ID per patient (`Unique Medical ID` / Medical File Number)  
- Automated archiving  
- Reduce human error  
- Automatic critical value detection  
- Electronic results & notifications (in-app, Email, WhatsApp)  
- PDF reports to doctors via email  
- Retain test history and compare current vs prior results  
- BI dashboards and operational KPIs  
- Fine-grained RBAC  
- Encrypt medical data  
- Store medical attachments  
- Reduce cost, time, and effort  

---

## 4. In Scope / Out of Scope

### In scope

Users & RBAC; patients; doctors; lab technicians; patient reception; test orders; sample tracking; result entry; result review; critical values & early warning; result delivery; WhatsApp; email; PDF reports; files/attachments; archive; historical comparison; BI dashboards; admin reports; backup; data protection.

### Explicitly out of scope (current phase)

- Inventory management (إدارة المخزون)  
- Complex financial accounting (المحاسبة المالية المعقدة)  
- Automatic medical diagnosis (التشخيص الطبي التلقائي) / AI diagnosis  
- Native mobile application (تطبيق هاتف)  

**Platform constraint from source:** Web-Based only, with PHP, MySQL, web UIs, and APIs.

---

## 5. End-to-End Lifecycle (summary)

```
Patient registration (unique Medical ID)
  → Doctor creates test order
  → Lab receives order & sample
  → Analysis / result entry (manual; analyzer integration TBD)
  → Programmatic critical-value engine
  → Review / approval
  → Notify doctor (email + critical alert) & patient (WhatsApp/in-app)
  → PDF generation & archive
  → BI / TAT / operational reports
```

Detailed workflow: see `BUSINESS_WORKFLOWS.md`.

---

## 6. Proposed Product Architecture (high level)

Monolithic modular **PHP** application (recommended framework: Laravel — see `DECISIONS.md`) with:

- Web UI (Arabic-first, RTL) for Admin, Lab, Doctor, Patient portals  
- REST (or JSON) API layer for portals and future mobile/analyzer adapters  
- MySQL relational store + object storage for attachments/PDFs  
- Async/queue workers for email, WhatsApp, PDF, backup  
- Audit log and backup/restore subsystem  

Full design: `SYSTEM_ARCHITECTURE.md`, modules: `MODULES.md`.

---

## 7. Repository State (Phase 0)

| Item | State |
|---|---|
| `/workspace` | Near-empty; README only on `main` |
| Source materials | `docs/source/` (DOCX + extracted MD) |
| Application code | **None** — Phase 0 forbids implementation |
| Planning docs | This directory (`docs/`) |

---

## 8. Document Map

| Document | Purpose |
|---|---|
| `PROJECT_OVERVIEW.md` | This file — product & scope summary |
| `REQUIREMENTS_MATRIX.md` | Every requirement → ID, module, phase, status, verification |
| `SYSTEM_ARCHITECTURE.md` | Layers, components, deployment |
| `MODULES.md` | Module boundaries & ownership |
| `DATABASE_DESIGN.md` | Schema, relationships, status model |
| `API_ARCHITECTURE.md` | API style, resources, auth |
| `ROLES_AND_PERMISSIONS.md` | RBAC matrix |
| `BUSINESS_WORKFLOWS.md` | Operational workflows |
| `IMPLEMENTATION_ROADMAP.md` | Phases, parallelization, agents |
| `TESTING_STRATEGY.md` | Test levels + AC mapping |
| `SECURITY_PLAN.md` | Controls mapped to requirements |
| `PROJECT_PROGRESS.md` | Permanent execution tracker |
| `DECISIONS.md` | Architectural/business decisions |
| `RISKS_AND_ASSUMPTIONS.md` | Gaps, contradictions, risks |
| `PHASE0_EXECUTIVE_REPORT.md` | Coordinator executive summary |
| `source/` | Original DOCX + extracted text |

---

## 9. Phase Gate

**Phase 0 ends with planning artifacts only.**  
**No application code, migrations, APIs, or UI may be built until explicit user approval of Phase 1.**
