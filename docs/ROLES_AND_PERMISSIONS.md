# LabBI — Roles and Permissions

**Status:** Phase 0 planning (not implemented)  
**Model:** Role-Based Access Control (RBAC) — AUTH-02, AUTH-03, AUTH-04

---

## 1. Formal Roles (from source)

| Role code | Arabic | English | Summary |
|---|---|---|---|
| `admin` | الإدارة | Administration | Full system, users, reports, BI, backup, settings |
| `lab_technician` | فني المختبر | Laboratory Technician | Orders, samples, results, lab files, review queue |
| `doctor` | الطبيب | Doctor | Patients (allowed), orders, results, history, comparison |
| `patient` | المريض | Patient | Own file, own results, notifications, PDF download, requests |

### Mentioned but not formalized (GAP-05 / PER-01)

| Persona | Handling |
|---|---|
| Receptionist (موظف استقبال) | **Open decision:** fifth role vs lab performs reception |
| Quality officers, dept heads, hospital managers, senior leaders, quality advisors | Marketing personas; no separate RBAC roles unless user expands scope. May consume admin BI reports via `admin` or future read-only role |

---

## 2. Permission Catalog (initial)

Permissions are codes checked by policies. Roles receive sets of permissions (USR-06).

### Users & RBAC
`users.view` `users.create` `users.update` `users.activate` `roles.manage` `permissions.manage`

### People
`patients.view_all` `patients.view_assigned` `patients.view_self` `patients.create` `patients.update`  
`doctors.manage` `lab_staff.manage`

### Catalog
`catalog.view` `catalog.manage` `ranges.manage`

### Orders / Samples / Results
`orders.view_all` `orders.view_own_doctor` `orders.view_lab` `orders.view_self` `orders.create` `orders.submit`  
`samples.receive` `samples.reject` `samples.start_analysis`  
`results.enter` `results.submit` `results.approve` `results.return` `results.view_all` `results.view_own_doctor` `results.view_self`  
`attachments.upload` `attachments.view`

### Critical / Comms / Docs
`critical.view_all` `critical.view_own` `notifications.view_self`  
`pdf.generate` `pdf.download_own` `pdf.download_clinical`  
`comparison.use` `requests.create` `requests.view_own` `requests.manage_doctor`

### BI / Ops
`bi.view` `reports.operational` `audit.view` `backups.manage` `settings.manage`

---

## 3. Role → Permission Matrix (MVP)

| Permission | Admin | Lab Tech | Doctor | Patient |
|---|---|---|---|---|
| users.* / roles.manage / permissions.manage | ✓ | — | — | — |
| patients.view_all / create / update | ✓ | ✓ (lab need) | view_assigned (+ create order path) | view_self |
| doctors.manage / lab_staff.manage | ✓ | — | — | — |
| catalog.view | ✓ | ✓ | ✓ | — |
| catalog.manage / ranges.manage | ✓ | limited? **TBD** | — | — |
| orders.create / submit | ✓ | — | ✓ | — |
| orders.view_* | all | lab queue | own doctor | self |
| samples.* | ✓ | ✓ | — | — |
| results.enter / submit | ✓ | ✓ | — | — |
| results.approve / return | ✓ | **TBD REV-05** | — | — |
| results.view | all | lab | own patients | self |
| critical.view | all | all lab | own | — (or own critical only TBD) |
| pdf.generate | ✓ | ✓ | — | — |
| pdf.download | clinical | clinical | clinical | own |
| comparison.use | ✓ | — | ✓ | — (or own TBD) |
| bi.view / operational reports | ✓ | limited lab reports | — | — |
| audit.view / backups / settings | ✓ | — | — | — |
| requests.create | — | — | — | ✓ |
| requests.manage_doctor | — | — | ✓ | — |

**TBD cells must be decided before P2** — see `DECISIONS.md`.

---

## 4. Row-Level Rules (AUTH-04 / AC-02)

| Actor | Rule |
|---|---|
| Patient | `patient_id` must equal own profile; deny all others |
| Doctor | Access patients/orders/results linked to self (or explicitly assigned) |
| Lab Technician | Access orders/samples/results in lab queues; patient demographics as needed for matching |
| Admin | Full access with audit |

---

## 5. Session & Account Rules

- Secure sessions (AUTH-05); password hashing (AUTH-06)  
- Deactivated users cannot authenticate (USR-04)  
- Patient may login with medical file number (PTP-01) — exact UX TBD  
- Password recovery not in source (GAP-11) — recommended for production  

---

## 6. Approval Authority (open)

Source requires review/approval but does not name approver. Options:

| Option | Description |
|---|---|
| A | Any lab technician who did not enter the result (four-eyes) |
| B | Dedicated Lab Supervisor / Head role (new role) |
| C | Admin only |
| D | Same technician may approve (faster; weaker control) |

**Recommendation:** Option A or B for medical safety; record choice before implementing `results.approve`.

---

## 7. Receptionist Decision (GAP-05)

| Option | Impact |
|---|---|
| No separate role | Lab tech / admin registers patients & receives samples |
| Add `receptionist` | Permissions: `patients.create/update`, `orders.view_lab`, `samples.receive` only |

Default planning assumption until user decides: **no fifth role**; reception performed by lab/admin.
