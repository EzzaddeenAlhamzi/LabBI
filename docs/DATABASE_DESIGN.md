# LabBI — Database Design

**Status:** Phase 0 planning (not implemented — no migrations yet)  
**Engine:** MySQL (TECH-03 / DB-01)  
**Charset:** `utf8mb4` (Arabic support)

---

## 1. Design Principles

- Relational system of record for clinical/ops workflow  
- Soft delete for patients and other clinical entities where appropriate (`deleted_at`)  
- Immutable audit trail (append-only `audit_logs`)  
- Unique business keys: `medical_file_number`, `order_number`, `sample_number`  
- Ranges live in catalog tables — Critical Engine never hardcodes clinical thresholds  
- Delivery channels logged separately from notification content  

---

## 2. Unified Status Model (resolves source conflict)

Source mixes: قيد التنفيذ / مكتمل / مرفوض and قيد التحليل / جاهز / مؤجل.

### Recommended `order_status` enum

| Status | Meaning |
|---|---|
| `requested` | Order submitted to lab |
| `sample_received` | Sample confirmed |
| `in_analysis` | Processing |
| `pending_review` | Results entered; awaiting approval |
| `ready` | Approved; awaiting patient/doctor delivery completion |
| `completed` | Delivered/closed |
| `archived` | Archived on patient file |
| `deferred` | مؤجل |
| `rejected` | مرفوض (order or sample path) |

### Sample status (subset)

`awaiting` → `received` → `rejected` | `in_analysis` → `completed`

### Result status

`draft` → `submitted` → `pending_review` → `approved` | `returned`

Mapping of Arabic source labels → enums documented in `DECISIONS.md` (WFL-02).

---

## 3. Entity Relationship Overview

```
users ──┬── doctors
        ├── laboratory_staff
        └── patients (optional user_id for portal login)

roles ◄──► users (user_roles)
roles ◄──► permissions (role_permissions)

patients ──< test_orders >── doctors
test_orders ──< test_order_items >── tests
test_orders ──< samples
test_order_items ──< results
tests ──< test_reference_ranges
results ──< result_attachments
results ──< result_reviews
results ──< critical_alerts
users ──< notifications ──< notification_deliveries
users ──< audit_logs
results/orders ──< generated_reports
backups (ops metadata)
departments ──< tests
```

---

## 4. Table Specifications

### 4.1 Identity & Access

#### `users`
| Column | Type | Notes |
|---|---|---|
| id | PK | |
| name | varchar | |
| email | varchar unique nullable | |
| phone | varchar nullable | |
| password_hash | varchar | bcrypt/Argon2 |
| is_active | boolean | USR-04 |
| last_login_at | datetime nullable | USR-08 |
| timestamps, deleted_at | | |

#### `roles`
`id`, `code` (admin|lab_technician|doctor|patient|[receptionist?]), `name_ar`, `name_en`

#### `permissions`
`id`, `code`, `description`

#### `user_roles`
`user_id`, `role_id` unique pair

#### `role_permissions`
`role_id`, `permission_id` unique pair

---

### 4.2 People Profiles

#### `patients`
| Column | Notes |
|---|---|
| id | PK |
| user_id | nullable FK → users (portal) |
| medical_file_number | **unique** Medical ID |
| full_name, date_of_birth, sex, phone, national_id_encrypted? | demographics |
| primary_doctor_id | nullable FK → doctors |
| timestamps, deleted_at | soft delete preferred |

#### `doctors`
`id`, `user_id` unique, `license_number`, `specialty`, `department_id?`

#### `laboratory_staff`
`id`, `user_id` unique, `department_id?`, `job_title`

#### `departments`
`id`, `code`, `name_ar`, `is_active`

---

### 4.3 Catalog

#### `tests`
`id`, `code` unique, `name_ar`, `name_en?`, `unit`, `department_id`, `is_active`

#### `test_reference_ranges`
| Column | Notes |
|---|---|
| id | PK |
| test_id | FK |
| normal_low, normal_high | decimal nullable |
| critical_low, critical_high | decimal nullable |
| sex / age_min / age_max | optional future refinement |
| effective_from / effective_to | versioning |
| is_active | |

**Governance:** clinical values entered/approved by medical authority (CAT-04), not invented by developers.

---

### 4.4 Orders & Samples

#### `test_orders`
`id`, `order_number` unique, `patient_id`, `doctor_id`, `department_id?`, `status`, `ordered_at`, `archived_at?`, `created_by`, timestamps

#### `test_order_items`
`id`, `test_order_id`, `test_id`, `status`, `notes`

#### `samples`
`id`, `sample_number` unique, `test_order_id`, `patient_id`, `test_id?` or `test_order_item_id`, `received_at`, `received_by`, `status`, `rejection_reason?`

---

### 4.5 Results & Review

#### `results`
| Column | Notes |
|---|---|
| id | PK |
| test_order_item_id | FK |
| patient_id | denormalized for query speed |
| value_numeric / value_text | support numeric + qualitative |
| unit | |
| classification | normal\|abnormal\|critical\|unknown |
| status | draft…approved |
| notes | |
| entered_by, entered_at | RES-11 |
| updated_by, updated_at | |
| approved_by, approved_at | |

#### `result_attachments`
`id`, `result_id`, `file_path`, `mime_type`, `original_name`, `uploaded_by`, `uploaded_at`

#### `result_reviews`
`id`, `result_id`, `reviewer_id`, `action` (approve\|return\|reject), `comment`, `reviewed_at`

---

### 4.6 Critical Alerts

#### `critical_alerts`
`id`, `result_id`, `patient_id`, `doctor_id`, `test_id`, `detected_value`, `threshold_snapshot` (JSON), `detected_at`, `notified_at`, `notification_status`, `acknowledged_at?`

Stores threshold snapshot so later catalog edits do not rewrite history.

---

### 4.7 Notifications & Deliveries

#### `notifications`
`id`, `type`, `user_id`, `patient_id?`, `test_order_id?`, `test_id?`, `message`, `is_read`, `created_at`

#### `notification_deliveries`
`id`, `notification_id`, `channel` (in_app\|email\|whatsapp), `status` (pending\|sent\|failed), `provider_ref?`, `error?`, `sent_at`

---

### 4.8 Reports, Audit, Backup

#### `generated_reports`
`id`, `result_id` / `test_order_id`, `file_path`, `generated_by`, `generated_at`

#### `audit_logs`
`id`, `user_id?`, `action`, `entity_type`, `entity_id`, `old_values` JSON, `new_values` JSON, `ip`, `created_at`

#### `backups`
`id`, `taken_at`, `status`, `size_bytes`, `storage_path`, `notes`

#### `patient_requests` (recommended for GAP-08)
`id`, `patient_id`, `doctor_id`, `result_id?`, `type` (interpretation\|retest), `message`, `status`, timestamps

---

## 5. Indexing (initial)

- `patients(medical_file_number)` unique  
- `patients(full_name)`, `patients(phone)`  
- `test_orders(order_number)` unique, `(status, ordered_at)`, `(patient_id)`, `(doctor_id)`  
- `results(patient_id, test_order_item_id)`, `(classification, created_at)`  
- `critical_alerts(detected_at)`, `(doctor_id, notified_at)`  
- `notifications(user_id, is_read)`  
- `audit_logs(entity_type, entity_id)`, `(created_at)`  

---

## 6. BI Read Model

MVP BI can query transactional tables with SQL aggregations.  
Optional later: materialized daily summary tables (`bi_daily_stats`) for performance (NFR-PERF-05) — not required to start.

---

## 7. Data Retention (GAP-10 — open)

No retention periods in source. Until decided, plan assumes:

- Clinical records retained indefinitely (soft delete only)  
- Attachments retained with results  
- Backups retained N days (TBD)  
- Audit logs retained indefinitely or per compliance decision  

---

## 8. Open Schema Questions

1. One result per order item vs multiple result versions? (recommend versioned rows or immutable approved snapshot)  
2. Age/sex-specific ranges in v1?  
3. Multi-lab tenancy column (`laboratory_id`) in v1?  
4. Encrypt which columns with AES-256?  

Record decisions in `DECISIONS.md` before migrations.
