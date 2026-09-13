# LabBI — Requirements Matrix

**Status values (post Phase 0):** `planning` | `not started` | `in progress` | `implemented` | `verified` | `approved` | `deferred` | `decided`  
**Source:** `docs/source/labbi-requirements-extracted.md`  
**Rule:** Nothing from the original analysis may be silently ignored. Mark implemented only when coded and verified. Phase 1–4 approved on main (PR #12); Phase 5 in progress — A8/A9 still required before production verdict.

### Legend — Implementation phase

| Phase | Name |
|---|---|
| P1 | Core platform |
| P2 | Laboratory workflow |
| P3 | Communication & documents |
| P4 | Intelligence (BI / comparison / TAT) |
| P5 | Security hardening, backup, production readiness |
| P0 | Planning / decision only (no build yet) |
| Future | Explicitly deferred / out of current product scope |

### Status values

`planning` = designed · `not started` = accepted · `in progress` = actively coding · `implemented`/`verified`/`approved` = done gates · `deferred` = later phase · `decided` = governance lock

---

## A. Authentication & Authorization

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| AUTH-01 | Login / authentication system | Auth | P1 | approved | Manual + automated login tests; AC-01 path |
| AUTH-02 | Support roles: Admin, Laboratory Technician, Doctor, Patient | Auth / RBAC | P1 | approved | Seed roles; role assignment tests; AC-01 |
| AUTH-03 | Apply RBAC | Auth / RBAC | P1 | approved | Permission matrix tests; AC-21 |
| AUTH-04 | Prevent access to unauthorized data | Auth / RBAC | P1 | approved | Cross-patient access tests; AC-02, AC-21 |
| AUTH-05 | Secure session management | Auth | P1 | approved | Session expiry / cookie security tests |
| AUTH-06 | Password hashing (source mentions Bcrypt & SHA-256; prefer bcrypt/Argon2 — see DECISIONS) | Auth | P1 | approved | Verify hashes not reversible; unit tests |

---

## B. User Management (إدارة المستخدمين)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| USR-01 | List users | Users | P1 | approved | Admin UI + API list tests |
| USR-02 | Create user | Users | P1 | approved | Create flow; AC-01 |
| USR-03 | Edit user | Users | P1 | approved | Update persistence tests |
| USR-04 | Activate / deactivate user | Users | P1 | approved | Disabled user cannot login |
| USR-05 | Assign role | Users / RBAC | P1 | approved | Role change reflected in access |
| USR-06 | Grant / revoke permissions | Users / RBAC | P1 | approved | Permission change audit + access tests |
| USR-07 | Search/filter by name, role, status | Users | P1 | approved | Filter query tests |
| USR-08 | Show last login | Users | P1 | approved | Last-login updated on auth |

---

## C. Patients (إدارة المرضى)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| PAT-01 | Create patient file | Patients | P1 | approved | Create patient; AC-03 |
| PAT-02 | Assign unique medical file number / Medical ID | Patients | P1 | approved | Uniqueness constraint tests; AC-03 |
| PAT-03 | Search by medical ID, name, phone | Patients | P1 | approved | Search fixture tests |
| PAT-04 | Edit patient data (prefer soft delete over hard delete for medical records) | Patients | P1 | approved | Update + soft-delete tests |
| PAT-05 | Show linked doctor(s) | Patients | P1 | approved | Relationship display tests |
| PAT-06 | Show all patient orders | Patients | P1 | approved | Order list on patient profile |
| PAT-07 | Show all patient results | Patients | P2 | verified | Results tab; AC-15 |
| PAT-08 | Show attachments | Patients / Attachments | P2 | verified | Attachments tab |
| PAT-09 | Show laboratory medical history | Patients / Archive | P2–P4 | verified | History view; AC-18 |
| PAT-10 | Compare prior results | Comparison | P4 | verified | Comparison UI; AC-19 |

---

## D. Test Catalog (دليل الفحوصات) — required for critical engine; underspecified medically in source

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| CAT-01 | Manage test definitions (name, code, unit, department, active status) | Test Catalog | P1 | approved | CRUD tests |
| CAT-02 | Define normal low/high reference ranges per test | Test Catalog | P1 | approved | Range CRUD; engine uses ranges |
| CAT-03 | Define critical low/high ranges per test | Test Catalog | P1 | approved | Critical ranges; CRT-* tests |
| CAT-04 | Medical authority must approve catalog values (not developer-authored clinical data) | Test Catalog / Governance | P0–P1 | planning | Sign-off checklist before go-live |
| CAT-05 | Admin screens: Test Catalog, Test Details, Reference/Critical Ranges | Test Catalog | P1 | approved | Screen inventory QA |

---

## E. Test Orders (طلبات الفحوصات)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| ORD-01 | Create test order | Orders | P1 | approved | Create order; AC-04 |
| ORD-02 | Link order to patient | Orders | P1 | approved | FK integrity; AC-04 |
| ORD-03 | Link order to doctor | Orders | P1 | approved | FK integrity; AC-04 |
| ORD-04 | Add one or more tests inside an order | Orders | P1 | approved | Multi-item order tests |
| ORD-05 | Generate unique order number | Orders | P1 | approved | Uniqueness constraint |
| ORD-06 | Record order date/time | Orders | P1 | approved | Timestamp assertions |
| ORD-07 | Send/submit order electronically to laboratory | Orders | P1 | approved | Appears on lab queue; AC-05 |
| ORD-08 | Display order status | Orders / Workflow | P1–P2 | verified | Status transitions visible |
| ORD-09 | Archive order | Orders / Archive | P2–P5 | verified | Archived flag/state; AC-18 |
| ORD-10 | Search orders | Orders | P1 | approved | Search tests |
| ORD-11 | Doctor can create test order (logical requirement; missing from one doctor feature list in source) | Orders / Doctor Portal | P1 | approved | Doctor Create Order screen; AC-04 |

---

## F. Samples (إدارة العينات)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| SMP-01 | Register sample number | Samples | P1 | approved | Sample create tests |
| SMP-02 | Link sample to order, patient, test | Samples | P1 | approved | Relationship tests |
| SMP-03 | Record receipt date/time and receiving staff | Samples | P1 | approved | Audit fields; AC-06 |
| SMP-04 | Track sample status | Samples / Workflow | P1–P2 | approved | Status machine tests |
| SMP-05 | Confirm sample receipt | Samples | P1 | approved | AC-06 |
| SMP-06 | Match sample to order | Samples | P1 | approved | Mismatch rejection path |
| SMP-07 | Reject sample when needed | Samples | P2 | verified | Rejected state + reason |
| SMP-08 | Start analysis | Samples / Workflow | P2 | verified | Transition to In Analysis |

---

## G. Order / Result Status Workflow (unify conflicting source statuses)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| WFL-01 | Unified status model: Requested → Sample Received → In Analysis → Pending Review → Ready → Completed → Archived; exceptions Deferred, Rejected | Workflow | P1–P2 | verified | State machine unit tests |
| WFL-02 | Map legacy source labels (قيد التنفيذ / مكتمل / مرفوض / قيد التحليل / جاهز / مؤجل) onto unified model | Workflow | P0–P1 | planning | Mapping table review in DECISIONS |
| WFL-03 | Lab dashboard queues reflect unified statuses | Lab Portal | P2 | verified | Dashboard queue tests |

---

## H. Result Entry (إدخال النتائج)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| RES-01 | Open order for result entry | Results | P2 | verified | Lab open-order flow; AC-07 |
| RES-02 | Show required tests on order | Results | P2 | verified | Item list display |
| RES-03 | Enter each result value | Results | P2 | verified | AC-07 |
| RES-04 | Enter / capture unit | Results | P2 | verified | Unit persistence |
| RES-05 | Save result as draft | Results | P2 | verified | Draft not notified |
| RES-06 | Validate result data | Results | P2 | verified | Invalid input rejected |
| RES-07 | Run critical value engine on save/submit | Results / Critical Engine | P2 | verified | AC-08, AC-09 |
| RES-08 | Add notes | Results | P2 | verified | Notes field tests |
| RES-09 | Upload attachments (PDF, images) | Attachments | P2 | verified | Upload + MIME validation |
| RES-10 | Approve / finalize result | Results / Review | P2 | verified | AC-13 |
| RES-11 | Audit who entered/modified/approved and when | Results / Audit | P2–P5 | verified | Audit log assertions |

---

## I. Critical Value Engine (محرك القيم الحرجة)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| CRT-01 | Accept result value | Critical Engine | P2 | verified | Engine input contract tests |
| CRT-02 | Load reference (normal) range for test | Critical Engine | P2 | verified | Fixture range lookup |
| CRT-03 | Load critical range for test | Critical Engine | P2 | verified | Fixture critical lookup |
| CRT-04 | Automatic comparison | Critical Engine | P2 | verified | AC-08, AC-09 |
| CRT-05 | Classify: Normal / Abnormal / Critical | Critical Engine | P2 | verified | Classification matrix tests |
| CRT-06 | Show color indicator | Critical Engine / UI | P2–P4 | verified | UI visual QA |
| CRT-07 | If Critical → create Critical Alert | Critical Engine | P2 | verified | AC-09, AC-10 |
| CRT-08 | Record discovery timestamp | Critical Engine | P2 | verified | AC-12 |
| CRT-09 | Notify doctor | Critical Engine / Notifications | P2–P3 | verified | AC-11 |
| CRT-10 | Record notification send timestamp | Critical Engine / Notifications | P3 | verified | AC-12 |
| CRT-11 | Include case on Critical Cases dashboard | Critical Engine / BI | P4 | verified | Dashboard presence |
| CRT-12 | Compute Turnaround Time (TAT) related metrics | Critical Engine / BI | P4 | verified | TAT calculation tests; AC-20 |

---

## J. Review & Approval (الاعتماد والمراجعة)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| REV-01 | Pending Review stage before approval | Review | P2 | verified | Workflow tests |
| REV-02 | Approve result | Review | P2 | verified | AC-13 |
| REV-03 | Reject / return for correction | Review | P2 | verified | Returned state tests |
| REV-04 | “Smart audit” / programmatic checks before approval (source: التدقيق الذكي) | Review / Critical Engine | P2 | verified | Block approval if unclassified / invalid |
| REV-05 | Who may approve results — **LOCKED:** Admin only via RBAC permission `results.approve` (not role-name checks); permission initially assigned only to Admin | Review / Governance | P0 (decision) / P2 (workflow UI) | decided | See D-008; P1 seeds permission only — approval UI/engine is P2 |

---

## K. Notifications (نظام التنبيهات)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| NTF-01 | Central Notification Center | Notifications | P3 | verified | Feature Phase3 + UI |
| NTF-02 | Notification fields: type, target user, patient, order, test, message, date, read status, channel, delivery status | Notifications | P3 | verified | Migration + Phase3 tests |
| NTF-03 | In-system notifications | Notifications | P3 | verified | notifications.index/show tests |
| NTF-04 | Email channel | Notifications / Email | P3 | verified | AC-16 |
| NTF-05 | WhatsApp channel | Notifications / WhatsApp | P3 | verified | AC-17; delivery log |
| NTF-06 | Doctor receives result after completion; critical receives immediate alert | Notifications | P3 | verified | AC-11, AC-15, AC-16 |
| NTF-07 | Patient notified when result ready (incl. WhatsApp) | Notifications | P3 | verified | AC-17 |

---

## L. Email

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| EML-01 | After approval, email doctor | Email | P3 | verified | AC-16 |
| EML-02 | Include basic patient data, test name, report status | Email | P3 | verified | Mail content + Mail::fake |
| EML-03 | Attach PDF or secure link | Email / PDF | P3 | verified | Secure link in mail + download ACL |

---

## M. WhatsApp

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| WA-01 | Integrate WhatsApp API (provider TBD — do not lock prematurely) | WhatsApp | P3 | verified | WhatsAppGateway + MockWhatsAppGateway |
| WA-02 | Notify patient of result readiness | WhatsApp | P3 | verified | AC-17 |
| WA-03 | Optionally send secure result link | WhatsApp | P3 | verified | Authenticated portal link |
| WA-04 | Log Sent / Failed / DateTime | WhatsApp | P3 | verified | notification_deliveries tests |

---

## N. PDF Reports

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| PDF-01 | Report generator for results | PDF | P3 | verified | AC-14 |
| PDF-02 | Include patient, test, result, lab, issue date (per available data) | PDF | P3 | verified | PDF blade fields |
| PDF-03 | Export and archive PDFs | PDF / Archive | P3 | verified | File stored + linked; AC-14, AC-18 |
| PDF-04 | Adopt approved template (logo, signature, QR, bilingual?) — **open** | PDF | P0–P3 | planning | Template sign-off |

---

## O. Patient Archive & Portal Sections

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| ARC-01 | Patient file contains all orders, tests, results, PDFs, images, dates, doctor, lab, status | Archive | P2–P3 | verified | Profile completeness QA; AC-18 |
| ARC-02 | Auto-archive results after completion | Archive | P2–P3 | verified | AC-18 |
| ARC-03 | Patient profile sections/tabs: Profile, Test Orders, Results, Medical Laboratory History, Attachments, Critical Results, Reports | Patients / Portal | P2–P4 | verified | Navigation QA |
| ARC-04 | Store medical attachments (PDF/images) | Attachments | P2 | verified | Upload/download ACL tests |

---

## P. Historical Comparison (المقارنة التاريخية)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| CMP-01 | Doctor selects patient, test, time range | Comparison | P4 | verified | Filter UI tests; AC-19 |
| CMP-02 | Show date, value, unit, status (Normal/Abnormal/Critical) | Comparison | P4 | verified | Table content tests |
| CMP-03 | Show trend: rising / falling / stable with color indicator | Comparison | P4 | verified | Trend logic tests; AC-19 |

---

## Q. Admin BI Dashboard & Reports (ذكاء الأعمال)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| BI-01 | Cards: patients, doctors, tests, today’s tests, in analysis, completed, deferred, rejected, critical cases | BI | P4 | verified | Card value tests; AC-20 |
| BI-02 | Performance: tests in period, avg completion time, critical count, critical handling time | BI | P4 | verified | Metric calculation tests; AC-20 |
| BI-03 | Reports by date, test type, lab/department (when enabled), status, performance | BI / Reports | P4 | verified | Report export/filter tests |
| BI-04 | BI may start with numbers/tables/cards/color indicators; charts optional later (source clarification) | BI | P4 | verified | MVP BI acceptance without mandatory charts |
| BI-05 | BI analyzes operational/lab data — does **not** diagnose disease | BI | P4 | verified | Scope review; no diagnosis features |

---

## R. Laboratory Technician Dashboard

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| LAB-01 | Queues: new orders, awaiting sample, in analysis, needs review, critical, ready, deferred, rejected | Lab Portal | P2 | verified | Queue fixture tests |
| LAB-02 | Quick actions: receive sample, open order, enter result, upload attachment, approve/send to review, create PDF | Lab Portal | P2–P3 | verified | Action permission + flow tests |

---

## S. Doctor Portal

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| DOC-01 | Doctor dashboard: orders, create order, patients, new results, critical results, patient history, comparison, PDFs | Doctor Portal | P1–P4 | planning | Screen inventory QA |
| DOC-02 | Account / login / receive results / comparison (source doctor minimum list) | Doctor Portal | P1–P4 | planning | Role smoke tests |
| DOC-03 | Access completed results | Doctor Portal | P3 | verified | AC-15 |

---

## T. Patient Portal (بوابة المريض)

| Requirement ID | Description | Module | Implementation phase | Status | Verification method |
|---|---|---|---|---|---|
| PTP-01 | Register account or login via medical file number | Patient Portal / Auth | P1 | planning | Auth path tests |
| PTP-02 | View own profile only | Patient Portal | P1 | planning | AC-02 |
| PTP-03 | View results and old results | Patient Portal | P2–P3 | verified | History ACL tests |
| PTP-04 | Download PDF | Patient Portal | P3 | verified | Download ACL; AC-14 |
| PTP-05 | Receive notifications | Patient Portal | P3 | verified | AC-17 |
| PTP-06 | View test status | Patient Portal | P2 | verified | Status visibility |
| PTP-07 | Request interpretation or re-test from doctor (channel TBD: prefer structured request, not chat) | Patient Portal / Requests | P3–P4 | verified | Request create/list tests (P3) |

---

## U. Screens Inventory (الشاشات المطلوبة)

Every screen below is a requirement derived from source §35.

### General

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| UI-G-01 | Login | Auth UI | P1 | approved | Visual + functional QA |
| UI-G-02 | Unauthorized | Auth UI | P1 | approved | 403 page |
| UI-G-03 | Profile | Users UI | P1 | approved | Profile edit smoke |
| UI-G-04 | Notifications | Notifications UI | P3 | verified | Inbox smoke |

### Admin

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| UI-A-01 | Admin Dashboard | BI | P4 | verified | Screen QA |
| UI-A-02 | Users | Users | P1 | approved | Screen QA |
| UI-A-03 | Create User | Users | P1 | approved | Screen QA |
| UI-A-04 | Edit User | Users | P1 | approved | Screen QA |
| UI-A-05 | Roles | RBAC | P1 | approved | Screen QA |
| UI-A-06 | Permissions | RBAC | P1 | approved | Screen QA |
| UI-A-07 | Patients | Patients | P1 | approved | Screen QA |
| UI-A-08 | Patient Details | Patients | P1 | approved | Screen QA |
| UI-A-09 | Doctors | Doctors | P1 | approved | Screen QA |
| UI-A-10 | Laboratory Staff | Lab Staff | P1 | approved | Screen QA |
| UI-A-11 | Test Catalog | Catalog | P1 | approved | Screen QA |
| UI-A-12 | Test Details | Catalog | P1 | approved | Screen QA |
| UI-A-13 | Reference/Critical Ranges | Catalog | P1 | approved | Screen QA |
| UI-A-14 | Orders | Orders | P1 | approved | Screen QA |
| UI-A-15 | Order Details | Orders | P1 | approved | Screen QA |
| UI-A-16 | Results | Results | P2 | verified | Screen QA |
| UI-A-17 | Critical Results | Critical | P2–P4 | verified | Screen QA |
| UI-A-18 | BI Dashboard | BI | P4 | verified | Screen QA |
| UI-A-19 | Operational Reports | BI | P4 | verified | Screen QA |
| UI-A-20 | Archived Reports | Archive | P3–P4 | verified | Screen QA (P3 archive/reports) |
| UI-A-21 | Audit Logs | Audit | P5 | planning | Screen QA |
| UI-A-22 | Backup Management | Backup | P5 | planning | Screen QA |
| UI-A-23 | System Settings | Settings | P5 | implemented | Screen QA (non-secret only) |

### Laboratory

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| UI-L-01 | Laboratory Dashboard | Lab Portal | P2 | verified | Screen QA |
| UI-L-02 | New Orders | Lab Portal | P1–P2 | approved | Screen QA |
| UI-L-03 | Order Details | Lab Portal | P1–P2 | approved | Screen QA |
| UI-L-04 | Sample Reception | Samples | P1 | approved | Screen QA |
| UI-L-05 | Samples | Samples | P1–P2 | approved | Screen QA |
| UI-L-06 | Result Entry | Results | P2 | verified | Screen QA |
| UI-L-07 | Result Review | Review | P2 | verified | Screen QA |
| UI-L-08 | Critical Alerts | Critical | P2 | verified | Screen QA |
| UI-L-09 | Attachments | Attachments | P2 | verified | Screen QA |
| UI-L-10 | Completed Tests | Results | P2 | planning | Screen QA |
| UI-L-11 | Laboratory Reports | Reports | P3–P4 | planning | Screen QA |
| UI-L-12 | Patient Search | Patients | P1 | planning | Screen QA |

### Doctor

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| UI-D-01 | Doctor Dashboard | Doctor Portal | P1–P4 | planning | Screen QA |
| UI-D-02 | My Patients | Doctor Portal | P1 | approved | Screen QA |
| UI-D-03 | Patient Profile | Doctor Portal | P1 | approved | Screen QA |
| UI-D-04 | Create Test Order | Orders | P1 | approved | Screen QA |
| UI-D-05 | Orders | Orders | P1 | approved | Screen QA |
| UI-D-06 | Results | Results | P2–P3 | verified | Screen QA |
| UI-D-07 | Critical Results | Critical | P2–P3 | planning | Screen QA |
| UI-D-08 | Result Details | Results | P2–P3 | verified | Screen QA |
| UI-D-09 | Historical Comparison | Comparison | P4 | verified | Screen QA |
| UI-D-10 | Reports/PDF | PDF | P3 | verified | Screen QA |

### Patient

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| UI-P-01 | Patient Dashboard | Patient Portal | P1–P3 | planning | Screen QA |
| UI-P-02 | My Tests | Patient Portal | P2 | planning | Screen QA |
| UI-P-03 | Result Details | Patient Portal | P2–P3 | planning | Screen QA |
| UI-P-04 | My History | Patient Portal | P2–P4 | planning | Screen QA |
| UI-P-05 | Download Report | PDF | P3 | verified | Screen QA |
| UI-P-06 | Notifications | Notifications | P3 | verified | Screen QA |
| UI-P-07 | Request Doctor Review/Re-test | Requests | P3–P4 | verified | Screen QA (P3 archive/reports) |

---

## V. Database & Data Model Requirements

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| DB-01 | Use MySQL | Database | P1 | planning | Connection + migrations |
| DB-02 | Store patients, orders, results, users, reports; link to BI | Database | P1–P4 | planning | Schema review |
| DB-03 | Implement proposed tables: users, roles, permissions, role_user, role_permissions, patients, doctors, laboratory_staff, departments, tests, test_reference_ranges, test_orders, test_order_items, samples, results, result_attachments, result_reviews, critical_alerts, notifications, notification_deliveries, audit_logs, generated_reports, backups | Database | P1–P5 | planning | Migration checklist vs DATABASE_DESIGN |
| DB-04 | Core relationships: Patient 1:N Orders; Doctor 1:N Orders; Order 1:N Items; Order 1:N Samples; Item↔Results; Test 1:N Ranges; Patient 1:N Results; Result 1:N Attachments; Result 1:N Critical Alerts; User 1:N Audit Logs | Database | P1 | planning | ERD review / FK tests |

---

## W. Audit Log

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| AUD-01 | Persist user, action, table/record, old value, new value, time, IP when needed | Audit | P5 (instrument from P1) | implemented | Audit write tests + Admin UI |
| AUD-02 | Must log: patient create/edit; order create; sample receive; result enter/edit/approve; critical discovery; alert send; report view; permission changes | Audit | P1–P5 | implemented | Event coverage checklist |

---

## X. Non-Functional Requirements

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| NFR-PERF-01 | Fast search | Platform | P1–P5 | planning | Indexed search smoke; no invented SLA yet |
| NFR-PERF-02 | Fast open of test files | Platform | P2–P3 | planning | Attachment open smoke |
| NFR-PERF-03 | Process orders without delay | Platform | P1–P2 | planning | Load smoke |
| NFR-PERF-04 | Send alerts without delay | Notifications | P3 | planning | Queue latency observation |
| NFR-PERF-05 | Generate BI reports quickly | BI | P4 | planning | Report timing smoke |
| NFR-SEC-01 | Encrypt data in transit | Security | P5 | implemented | HTTPS enforcement |
| NFR-SEC-02 | Encrypt sensitive data at rest (AES-256) | Security | P5 | implemented | Field encryption tests |
| NFR-SEC-03 | Authentication | Auth | P1 | approved | AUTH-* |
| NFR-SEC-04 | Authorization / RBAC | RBAC | P1 | approved | AUTH-03/04 |
| NFR-REL-01 | Availability ≥ 99% | Ops | P5 | implemented | Health endpoint + ops monitoring post-deploy |
| NFR-UX-01 | Simple UI for non-technical users | UI | P1–P5 | implemented | Usability review |
| NFR-UX-02 | Full Arabic language UI | UI / i18n | P1 | approved | AC-24 |
| NFR-SCALE-01 | Support adding users, patients, departments, labs | Platform | P1–P5 | approved | Multi-dept data model readiness |
| NFR-BK-01 | Automatic daily backup | Backup | P5 | implemented | AC-22 |
| NFR-BK-02 | Restore capability | Backup | P5 | implemented | AC-23 |
| NFR-COMPAT-01 | Supported browsers | Frontend | P5 | implemented | AC-25 (A9 re-confirm) |
| NFR-COMPAT-02 | Future-ready for analyzers and mobile apps | Architecture | Future | deferred | API/modular design review |
| NFR-MAINT-01 | Modular architecture | Architecture | P1 | approved | Module boundary review |
| NFR-MAINT-02 | Code documentation | Engineering | P1–P5 | in progress | Doc checklist |
| NFR-MAINT-03 | Easy addition of new tests | Catalog | P1 | approved | Add-test smoke |

---

## Y. Security Controls (explicit + recommended)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| SEC-01 | HTTPS/SSL | Security | P5 | implemented | TLS config |
| SEC-02 | Password hashing | Auth | P1 | approved | AUTH-06 |
| SEC-03 | AES-256 for sensitive data | Security | P5 | implemented | Encryption unit tests |
| SEC-04 | Protect against SQL Injection | Security | P1 | approved | ORM/prepared statements review + tests |
| SEC-05 | Protect against XSS | Security | P1 | approved | Escaping / CSP tests |
| SEC-06 | RBAC | RBAC | P1 | approved | AUTH-03 |
| SEC-REC-01 | CSRF protection (recommendation beyond literal source) | Security | P1 | approved | CSRF token tests |
| SEC-REC-02 | Server-side validation | Security | P1 | approved | Invalid payload tests |
| SEC-REC-03 | Login rate limiting | Security | P1–P5 | implemented | Brute-force tests |
| SEC-REC-04 | Secure cookies | Security | P1 | implemented | Cookie flags |
| SEC-REC-05 | Session expiration | Security | P1 | approved | AUTH-05 |
| SEC-REC-06 | Prepared statements / ORM | Security | P1 | approved | Code review |
| SEC-REC-07 | Do not expose internal errors to users | Security | P1 | implemented | Error page QA |
| SEC-REC-08 | Security event logging | Audit / Security | P5 | implemented | Security log presence |

---

## Z. Backup

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| BKP-01 | Automatic daily backup | Backup | P5 | implemented | AC-22 |
| BKP-02 | Record date, status, size, storage location | Backup | P5 | implemented | Metadata assertions |
| BKP-03 | Restore for emergency recovery | Backup | P5 | implemented | AC-23 |

---

## AA. Technology & Hosting Constraints

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| TECH-01 | Frontend: HTML, CSS, JavaScript | Frontend | P1 | planning | Stack compliance review |
| TECH-02 | Backend: PHP | Backend | P1 | planning | Stack compliance review |
| TECH-03 | Database: MySQL | Database | P1 | planning | Stack compliance review |
| TECH-04 | Security stack: HTTPS/SSL, AES-256, password hashing | Security | P1–P5 | planning | SECURITY_PLAN |
| TECH-05 | IDE noted: VS Code (non-binding for product) | Tooling | P0 | planning | N/A product AC |
| TECH-06 | Laravel chosen as PHP framework (source optional; locked for LabBI V1) | Backend | P0–P1 | decided | D-003 locked |
| HOST-01 | Support Local Server deployment | Ops | P5 | planning | Install guide dry-run |
| HOST-02 | Support Cloud Hosting deployment | Ops | P5 | planning | Deploy dry-run |
| HOST-03 | Build for Linux/web server without single-vendor lock-in | Ops | P5 | planning | Portable deploy review |

---

## AB. Analyzer Integration (conflict in source)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| ANL-01 | Source mentions direct programmatic receipt of analyzer results | Integrations | Future | deferred (Future) | Adapter-ready stubs only; no implementation in V1 |
| ANL-02 | SWOT treats analyzer integration as future expansion | Integrations | Future | deferred (Future) | Locked with D-006 |
| ANL-03 | Device type, vendor, model, protocol, API, connection, format required before any Future coding | Integrations | Future | deferred (Future) | Hardware questionnaire before Future work |

**LOCKED (D-006 / GAP-07):** V1 = Manual Result Entry only. Analyzer/HL7/ASTM/vendor integration = **Future**.

---

## AC. Explicit Non-Goals (must not build unless scope changes)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| OUT-01 | Do not build inventory system | — | Future | planning | Scope gate review |
| OUT-02 | Do not build full financial / accounting system | — | Future | planning | Scope gate review |
| OUT-03 | Do not build automatic disease diagnosis / diagnostic AI | — | Future | planning | Scope gate review |
| OUT-04 | Do not build native mobile app in current phase | — | Future | planning | Scope gate review |

---

## AD. Acceptance Criteria (AC-01 … AC-25)

| Requirement ID | Description | Module | Phase | Status | Verification method |
|---|---|---|---|---|---|
| AC-01 | Admin can create doctor, technician, patient and assign permissions | Users / RBAC | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-02 | Patient cannot access another patient’s data | RBAC / Patients | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-03 | Create patient with unique medical ID | Patients | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-04 | Create order linked to patient and doctor | Orders | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-05 | Order appears to laboratory | Orders / Lab | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-06 | Confirm sample receipt | Samples | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-07 | Enter result | Results | P2 | planning | E2E |
| AC-08 | Normal result does not create Critical Alert | Critical Engine | P2 | planning | E2E |
| AC-09 | Over critical limit → automatic detection | Critical Engine | P2 | planning | E2E |
| AC-10 | Critical Alert appears immediately | Critical Engine | P2 | planning | E2E |
| AC-11 | Doctor is notified | Notifications | P2–P3 | verified | Feature Phase3AcceptanceTest |
| AC-12 | Discovery and notification timestamps recorded | Critical / Audit | P2–P3 | verified | Data assertions |
| AC-13 | Result can be approved | Review | P2 | planning | E2E |
| AC-14 | PDF can be generated | PDF | P3 | verified | Feature Phase3AcceptanceTest |
| AC-15 | Doctor can access result | Doctor Portal | P3 | verified | Feature Phase3AcceptanceTest |
| AC-16 | Email sent to doctor | Email | P3 | verified | Feature Phase3AcceptanceTest / mail trap |
| AC-17 | Patient receives readiness notification | Notifications / WA | P3 | verified | Feature Phase3AcceptanceTest |
| AC-18 | Exam archived | Archive | P2–P3 | verified | Feature Phase3AcceptanceTest |
| AC-19 | Doctor can compare new vs prior exam | Comparison | P4 | verified | Feature Phase4AcceptanceTest |
| AC-20 | Manager sees test counts and completion times | BI | P4 | verified | Feature Phase4AcceptanceTest |
| AC-21 | Permissions block unauthorized access | RBAC | P1 | verified | Feature test Phase1AcceptanceTest |
| AC-22 | Daily backup executes | Backup | P5 | implemented | Ops E2E + automated |
| AC-23 | Backup restore succeeds | Backup | P5 | implemented | Ops E2E + automated |
| AC-24 | UI works in Arabic | i18n | P1 | verified | RTL Arabic shell assert + manual QA |
| AC-25 | UIs work on target browsers | Frontend | P5 | implemented | Cross-browser QA (A9 re-confirm) |

---

## AE. Gaps Explicitly Called Out in Source §47 (tracked)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| GAP-01 | Doctor create-order must be adopted | Orders | P1 | planning | ORD-11 / UI-D-04 |
| GAP-02 | Reference/critical range governance undefined | Catalog | P0 | planning | CAT-04 decision |
| GAP-03 | Approver role undefined | Review | P0 | decided | REV-05 → Admin via `results.approve` (D-008) |
| GAP-04 | Conflicting statuses need unified workflow | Workflow | P0–P1 | planning | WFL-01/02 / D-005 |
| GAP-05 | Receptionist role mentioned in feasibility but not as formal user | Users | P0 | decided | **No Receptionist in V1** (D-009); do not seed |
| GAP-06 | WhatsApp provider unspecified | WhatsApp | P0 | planning | Provider decision |
| GAP-07 | Analyzer integration unresolved | Integrations | P0 | decided | **Future** — manual entry V1 (D-006) |
| GAP-08 | Patient–doctor communication channel unspecified | Requests | P0 | planning | Prefer structured request |
| GAP-09 | PDF template unspecified | PDF | P0 | planning | Template decision |
| GAP-10 | No data retention policy | Compliance | P0 | planning | Retention decision |
| GAP-11 | No password recovery flow specified | Auth | P1 (recommended) | planning | Recovery AC to add after decision |
| GAP-12 | No detailed ERD in source | Database | P0 | planning | DATABASE_DESIGN.md |
| GAP-13 | No full wireframes in source | UI/UX | P1 design | planning | Wireframe milestone |
| GAP-14 | Timeline section empty in source | Planning | P0 | planning | IMPLEMENTATION_ROADMAP.md |
| GAP-15 | Blank page 20 — no extra requirements | — | P0 | planning | Noted; no reqs lost |
| GAP-16 | Academic cost figures not real implementation pricing | Planning | P0 | planning | Ignore for engineering estimates |

---

## AF. Problem / Goal Traceability (source §3–§4)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| PRB-01 | Address manual/non-unified workflows | Platform | P1–P5 | planning | End-to-end digital flow |
| PRB-02 | Prevent lost/damaged paper reports & transcription errors | Archive / Results | P2–P3 | planning | Digital archive + audit |
| PRB-03 | Automated critical value detection | Critical Engine | P2 | planning | CRT-* / AC-08–12 |
| PRB-04 | Faster delivery to doctor & patient | Notifications | P3 | planning | AC-16/17 |
| PRB-05 | Archive history & enable comparison | Archive / Comparison | P3–P4 | planning | AC-18/19 |
| PRB-06 | Admin monitoring of lab performance | BI | P4 | planning | AC-20 |
| PRB-07 | Strong permissions, security, encryption | Security / RBAC | P1–P5 | planning | SEC-* / AC-21 |
| PRB-08 | Archive PDF/images/complex reports | Attachments / PDF | P2–P3 | planning | ARC-04 / PDF-* |
| PRB-09 | Reduce operational cost/time vs paper | Platform | P1–P5 | planning | Process time metrics |
| GOL-01…GOL-16 | Goals listed in source §4 (unified system, unique ID, archive, fewer errors, critical detection, notifications, WhatsApp, email PDF, history, comparison, BI, KPIs, RBAC, encryption, attachments, reduce cost) | Multiple | P1–P5 | planning | Mapped via AUTH/PAT/CRT/NTF/WA/EML/PDF/CMP/BI/SEC rows above |

---

## AG. Marketing / Extended Personas (mentioned but not formal functional users)

| Requirement ID | Description | Module | Phase | Status | Verification |
|---|---|---|---|---|---|
| PER-01 | Quality/accreditation officers, department heads, hospital managers, senior leaders, quality advisors appear in marketing context only | — | Future / optional reporting views | planning | Do not invent roles without decision; BI reports may later serve them |

---

## Coverage Statement

This matrix includes:

1. All numbered AUTH/USR/PAT/ORD/RES/CRT items and AC-01…AC-25 from the programmer-ready section  
2. Sample, catalog, workflow, notification, email, WhatsApp, PDF, archive, comparison, BI, portal, screen, DB, audit, NFR, security, backup, hosting, analyzer, and out-of-scope items from the full analytical report  
3. Every gap from source §47 as tracked GAP-* rows  
4. Problem/goal statements so they are not silently dropped  

**Count (approx.):** 200+ discrete requirement rows across sections A–AG.  
Any future source amendment must add a new ID rather than silently editing intent.
