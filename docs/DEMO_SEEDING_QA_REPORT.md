# LabBI — Demo Seeding QA Report

**Date:** 2026-11-16  
**Branch:** `cursor/demo-seeding-system-ecb4` (uncommitted pending PO approval)  
**Environment:** PHP 8.3 + SQLite file DB on Cloud Agent VM  

---

## Automated tests

| Suite | Result |
|-------|--------|
| `php artisan test --filter=LabBIDemoSeederTest` | **4 passed** (production refuse, SMALL volume, idempotency, DatabaseSeeder does not auto-run volume) |
| Full `php artisan test` | **125 passed**, **4 failed** — failures are **pre-existing** on `main` (`Phase4AcceptanceTest` / `Phase4A9QaGapTest` historical comparison empty rows / TAT metric). Confirmed by stashing demo changes and re-running. |

---

## Profile runs (seed=42) — implementer

### SMALL (~1.9s)

| Metric | Count |
|--------|------:|
| patients / doctors / lab_staff | 50 / 10 / 5 |
| tests | 2 |
| orders / items | 100 / 175 |
| samples / results / reviews / alerts | 145 / 125 / 94 / 9 |
| notifications / deliveries / pdfs / requests | 325 / 325 / 25 / 18 |

### MEDIUM (~16s)

| Metric | Count |
|--------|------:|
| patients / doctors / lab_staff | 1000 / 30 / 15 |
| tests | 8 |
| orders / items | 3000 / ~5000–5250 |
| samples / results / reviews / alerts | see A8 for independent recount |
| notifications / deliveries / pdfs / requests | see A8 |

### LARGE (~138s)

| Metric | Count |
|--------|------:|
| patients / doctors / lab_staff | 5000 / 100 / 40 |
| tests | 20 |
| orders / items | 20000 / **35000** |
| samples / results / reviews / alerts | 29000 / 25000 / 18800 / 1800 |
| notifications / deliveries / pdfs / requests | 61563 / 61563 / 150 / 163 |

---

## Checks performed (implementer)

- [x] Production env refused  
- [x] Canonical admin password still `password`  
- [x] No volume data from default `DatabaseSeeder`  
- [x] Idempotent re-run (SMALL)  
- [x] Mixed valid order statuses including archived + pending_review + requested  
- [x] Critical alerts + notifications for critical band  
- [x] PDF paths under local disk `reports/demo/…` (capped)  
- [x] Mail faked / WhatsApp mock (no external send)  
- [x] CAT-04 disclaimer in result notes / guide  

---

## Residual risks

- Pre-existing Phase4 comparison/TAT test failures on `main` — out of scope.  
- LARGE on slow disks may take several minutes; tune `LABBI_DEMO_CHUNK`.  
- Clinical ranges still demo-only (LB-2 / CAT-04).  
- MEDIUM item count is approximate (~5000 target; A8 measured **5250** with current distribution).

---

## A8 Independent QA (2026-11-16)

**Reviewer:** A8 independent QA (`bc-62d9e21e-36ca-5fae-a98e-a74d31dcc44a`)  
**Detail report:** `/cursor/stores/bc-97b82a02-8fdc-4ee7-b7fd-3796db4a7e86/internal/demo-seeding-a8-qa.md`  
**Harness:** `/tmp/a8-demo-qa-verify.php` → `/tmp/a8-qa-result.json`  
**Git:** no commit / no push

### Verdict: **PASS WITH NOTES**

| Check | Result |
|-------|--------|
| SMALL run (seed=99) | PASS — 50/10/5, 100 orders, 175 items (~1.7s) |
| MEDIUM run (seed=99) | PASS WITH NOTE — 1000/30/15, 3000 orders, **5250 items** (~16s); approx target was ~5000 |
| LARGE | Not re-run by A8 (optional) |
| FK integrity (orphan JOINs) | PASS — zero orphans across orders/items/samples/results/alerts/notifications/deliveries/PDFs/requests/people |
| Unique constraints | PASS — no duplicate emails, MFNs, ORD/SMP numbers, result-per-item, dedupe/idempotency keys |
| Workflow validity | PASS — valid statuses, chronology, samples/results consistency, critical↔alert alignment |
| Idempotent re-run | PASS — SMALL + MEDIUM counts unchanged after second run |
| Mail::fake / WhatsApp mock | PASS — `assertNothingSent`; WA `mock-wa-demo`; email `demo` stubs |
| Canonical accounts | PASS — four `*@labbi.local` passwords still `password`; MF-000001, HB, GLU kept |
| Production refuse | PASS |
| `LabBIDemoSeederTest` | PASS — 4/4 |
| Full `php artisan test` | 125 passed / 4 failed — **same Phase4 failures on clean main** |
| Dashboards | PASS — DB counts non-empty; admin HTTP 200 on `/admin`, orders, patients, BI, critical-cases, operational, notifications; PDF files exist on disk |

### A8 notes

1. MEDIUM **items=5250** vs documented approximate **~5000** (1.75×3000). Acceptable under “approximate” wording; reconcile docs or distribution if exact 5000 is a hard gate.  
2. LARGE left optional — prior implementer run not independently re-measured this pass.  
3. No external notification delivery observed.

### A8 residual

None blocking merge of demo seeder for DEVELOPMENT use. Track MEDIUM item-count doc accuracy and CAT-04 separately.
