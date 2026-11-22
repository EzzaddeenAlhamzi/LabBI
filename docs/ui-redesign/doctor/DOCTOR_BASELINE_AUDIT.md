# Doctor Baseline Audit

- Role: `doctor` (custom permission system; routes under `doctor.` prefix gated by permissions: orders.view_own_doctor, orders.create, results.view_own_doctor, critical.view_own, pdf.download_clinical, comparison.use, requests.manage_doctor).
- Phase 3 branched from `feature/laboratory-staff-ui-redesign` (Phase 2, PR #19) as `feature/doctor-ui-redesign`.
- Baseline: 120 passed / 4 pre-existing Phase4 failures (HistoricalComparisonService).
- Doctor views discovered: 12 Blade templates — dashboard, orders index/create/show, patients index/show, results index/show, reports index, requests index, critical index, comparison index.
- Doctor controllers: 7 under app/Http/Controllers/Doctor/.
- Shared pages extended: profile + notifications (new-UI condition now covers admin, lab_technician, doctor).
