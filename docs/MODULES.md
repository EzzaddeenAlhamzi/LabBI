# LabBI — Modules

**Status:** Phase 0 planning (not implemented)  
Modules are bounded contexts inside the modular monolith. Each has a clear owner agent (see `IMPLEMENTATION_ROADMAP.md`) to avoid conflicting edits.

---

## Module Catalog

| ID | Module | Arabic | Responsibility | Depends on | Primary phase |
|---|---|---|---|---|---|
| M01 | Auth & Sessions | المصادقة | Login, logout, sessions/tokens, password hashing, password recovery (if approved) | — | P1 |
| M02 | RBAC | الصلاحيات | Roles, permissions, grants, policy checks | M01 | P1 |
| M03 | Users | المستخدمون | Admin CRUD for users; activate/deactivate; last login | M01, M02 | P1 |
| M04 | Doctors | الأطباء | Doctor profiles linked to users | M03 | P1 |
| M05 | Laboratory Staff | فنيو المختبر | Lab technician profiles | M03 | P1 |
| M06 | Patients | المرضى | Patient files, unique Medical ID, search, soft delete | M03 | P1 |
| M07 | Departments | الأقسام | Lab sections/departments for catalog & reports | — | P1 |
| M08 | Test Catalog | دليل الفحوصات | Tests, codes, units, active flag | M07 | P1 |
| M09 | Reference & Critical Ranges | القيم المرجعية والحرجة | Normal/critical low-high per test | M08 | P1 |
| M10 | Orders | طلبات الفحص | Create/submit/search/archive orders; order items | M04, M06, M08 | P1 |
| M11 | Samples | العينات | Receive, match, reject, start analysis | M10 | P1–P2 |
| M12 | Workflow | حالات الطلب | Unified status machine for orders/samples/results | M10, M11, M13 | P1–P2 |
| M13 | Results | النتائج | Draft/enter/validate results; notes; linkage to items | M10, M08 | P2 |
| M14 | Attachments | المرفقات | PDF/image upload storage & ACLs | M13, M06 | P2 |
| M15 | Critical Value Engine | محرك القيم الحرجة | Classify values; create alerts; timestamps | M09, M13 | P2 |
| M16 | Review & Approval | المراجعة والاعتماد | Pending review → approve / return | M13, M15 | P2 |
| M17 | Notifications | مركز التنبيهات | In-app notifications + orchestration | M01 | P3 |
| M18 | Email Delivery | البريد الإلكتروني | Doctor emails with PDF/link | M17, M19 | P3 |
| M19 | PDF Reports | تقارير PDF | Generate, store, download reports | M13, M16 | P3 |
| M20 | WhatsApp Delivery | واتساب | Patient readiness messages; delivery log | M17 | P3 |
| M21 | Patient Requests | طلبات التواصل | Interpretation / re-test requests (not chat) | M06, M04 | P3–P4 |
| M22 | Archive | الأرشفة | Completed/archived views on patient file | M10, M13, M19 | P2–P3 |
| M23 | Historical Comparison | المقارنة التاريخية | Trends across same test over time | M13 | P4 |
| M24 | BI & Operational Reports | ذكاء الأعمال | Cards, TAT, filters, exports | M10–M16 | P4 |
| M25 | Audit Log | سجل العمليات | Append-only activity trail | All | P1 instrument / P5 UI |
| M26 | Backup & Restore | النسخ الاحتياطي | Daily backup metadata + restore | Infra | P5 |
| M27 | System Settings | إعدادات النظام | App config, channels toggles | — | P5 |
| M28 | Analyzer Integration (optional) | تكامل الأجهزة | Future adapter for auto results | M13 | Future (default) |

---

## Portal Composition

| Portal | Modules primarily composing UI |
|---|---|
| Admin | M03–M09, M10, M15, M24–M27 |
| Laboratory | M10–M16, M19, M06 search |
| Doctor | M06, M10, M13, M15, M19, M23 |
| Patient | M06, M13, M17, M19, M21, M22 |

---

## Non-Overlapping Ownership Rules

1. **Only M15** owns critical classification logic. Results module calls it; UI never reimplements thresholds.  
2. **Only M12** owns legal status transitions. Other modules request transitions via Workflow API.  
3. **Only M17** creates user-visible notifications; Email/WhatsApp are delivery adapters.  
4. **Only M25** writes audit records via a shared writer — modules emit events, do not invent parallel logs.  
5. **M28** must not fork result entry; it posts into M13 through a validated ingestion port.

---

## Out-of-Module (Forbidden)

Inventory, accounting, diagnostic AI, native mobile shells — not modules in current scope (`OUT-*`).

---

## Suggested Package Layout (when implementation starts)

```
app/
  Domain/{Module}/
  Application/{Module}/
  Infrastructure/{Module}/
  Http/{Admin|Lab|Doctor|Patient}/
```

Exact folder conventions deferred to framework decision (`DECISIONS.md`).
