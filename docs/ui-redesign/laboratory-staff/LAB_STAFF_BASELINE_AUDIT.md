# Lab Staff Baseline Audit

- Role: `lab_technician` (custom permission system via `User::hasPermission()` / `EnsurePermission`; no Laravel Gate).
- Phase 1 branch `feature/admin-ui-redesign` (commit 5a61406, PR #18) preserved; Phase 2 branched from it as `feature/laboratory-staff-ui-redesign`.
- Baseline tests before changes: 120 passed / 4 failed (pre-existing Phase4 historical-comparison failures in `HistoricalComparisonService` — unrelated to UI).
- Lab views discovered: 14 Blade templates under `resources/views/lab/` (dashboard, orders index/show, samples index/reception, results entry-index/entry/review-index/review-show, critical index/show, attachments index, completed index, patients search).
- Lab controllers: 9 under `app/Http/Controllers/Lab/`.
- Shared pages reused: notifications index/show, profile — extended Phase 1 markup to `lab_technician` via the existing `$isAdmin` condition (renamed semantics: new UI for admin + lab staff; doctor/patient keep legacy markup).
