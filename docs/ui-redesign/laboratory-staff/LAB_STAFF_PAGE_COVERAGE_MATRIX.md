# Lab Staff Page Coverage Matrix

All 14 lab views + 3 shared views redesigned. 100% coverage of discovered lab interfaces.

| Module | Page | Route | View | Redesigned | Functional | Desktop | Tablet | Mobile |
|---|---|---|---|---|---|---|---|---|
| Dashboard | lab.dashboard | GET /lab | lab/dashboard | Yes | Yes (test_lab_portal_screens_render) | Yes* | Yes* | Yes* |
| Orders | index | lab.orders.index | lab/orders/index | Yes | Yes | Yes* | Yes* | Yes* |
| Orders | show | lab.orders.show | lab/orders/show | Yes | Yes | Yes* | Yes* | Yes* |
| Samples | index | lab.samples.index | lab/samples/index | Yes | Yes | Yes* | Yes* | Yes* |
| Samples | reception | lab.samples.reception | lab/samples/reception | Yes | Yes | Yes* | Yes* | Yes* |
| Results | entry index | lab.results.entry.index | lab/results/entry-index | Yes | Yes | Yes* | Yes* | Yes* |
| Results | entry | lab.results.entry | lab/results/entry | Yes | Yes | Yes* | Yes* | Yes* |
| Results | review index | lab.results.review.index | lab/results/review-index | Yes | Yes | Yes* | Yes* | Yes* |
| Results | review show | lab.results.review.show | lab/results/review-show | Yes | Yes | Yes* | Yes* | Yes* |
| Critical | index | lab.critical.index | lab/critical/index | Yes | Yes | Yes* | Yes* | Yes* |
| Critical | show | lab.critical.show | lab/critical/show | Yes | Yes | Yes* | Yes* | Yes* |
| Attachments | index | lab.attachments.index | lab/attachments/index | Yes | Yes | Yes* | Yes* | Yes* |
| Completed | index | lab.completed.index | lab/completed/index | Yes | Yes | Yes* | Yes* | Yes* |
| Patients | search | lab.patients.search | lab/patients/search | Yes | Yes | Yes* | Yes* | Yes* |
| Shared | notifications | notifications.* | notifications/* | Yes (admin+lab markup) | Yes | Yes* | Yes* | Yes* |
| Shared | profile | profile | profile | Yes (admin+lab markup) | Yes | Yes* | Yes* | Yes* |

*Responsive verification via shared Phase 1 breakpoints (grid collapse <1024px, drawer <900px); per-viewport screenshots pending Product Owner environment.
