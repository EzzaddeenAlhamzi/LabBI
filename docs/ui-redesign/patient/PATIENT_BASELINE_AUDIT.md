# Patient Baseline Audit

- Role: patient (patients.view_self + results.view_self/orders.view_self + requests.* permissions).
- Phase 4 branched from feature/doctor-ui-redesign (Phase 3, PR #20).
- Baseline: 121 passed / 4 pre-existing Phase4 failures.
- Patient views: 6 — dashboard, profile, tests/index, results/show, results/status, requests/index. records.show reuses profile view.
- Controllers: PatientDashboardController, MyTestsController, RequestController.
- Shared pages extended: profile + notifications new-UI markup now covers all roles.
