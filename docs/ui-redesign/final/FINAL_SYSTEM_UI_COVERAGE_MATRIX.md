# Final System UI Coverage Matrix

Status key: PASS = redesigned + HTTP-verified 200 + compiled; PASS WITH NOTES = verified structurally, not pixel-tested; NOT VERIFIED = requires populated data/browser.

## Admin — all PASS (HTTP 200, admin.css, view:cache OK)
| Page | Route | Status |
|---|---|---|
| Dashboard | /admin | PASS |
| BI Dashboard | /admin/bi | PASS |
| Users list/create/edit | /admin/users* | PASS |
| Roles list/show | /admin/roles* | PASS |
| Permissions | /admin/permissions | PASS |
| Patients list/create/show/edit | /admin/patients* | PASS |
| Doctors list/create | /admin/doctors* | PASS |
| Lab staff list/create | /admin/lab-staff* | PASS |
| Departments | /admin/departments | PASS |
| Tests list/create/show | /admin/tests* | PASS |
| Reference ranges | /admin/ranges | PASS |
| Orders list/create/show | /admin/orders* | PASS |
| Audit logs list/show | /admin/audit-logs* | PASS |
| Backups list/show | /admin/backups* | PASS |
| Settings | /admin/settings | PASS |
| Deliveries | /admin/deliveries | PASS |
| Operational report | /admin/reports/operational | PASS |
| Critical cases | /admin/critical-cases | PASS |
| Profile | /profile | PASS |
| Notifications | /notifications | PASS |

## Laboratory Staff — all PASS
| Page | Route | Status |
|---|---|---|
| Dashboard | /lab | PASS |
| Orders list/show | /lab/orders* | PASS (detail: NOT VERIFIED — empty DB) |
| Samples list | /lab/samples | PASS |
| Sample reception | /lab/samples/reception | PASS |
| Patient search | /lab/patients/search | PASS |
| Result entry index/per-order | /lab/results/entry* | PASS (per-order: NOT VERIFIED — empty DB) |
| Result review list/show | /lab/results/review* | PASS (detail: NOT VERIFIED) |
| Completed tests | /lab/completed | PASS |
| Critical alerts list/show | /lab/critical* | PASS |
| Attachments | /lab/attachments | PASS |
| Profile/Notifications | /profile, /notifications | PASS |

## Doctor — all PASS
| Page | Route | Status |
|---|---|---|
| Dashboard | /doctor | PASS |
| Patients list/show | /doctor/patients* | PASS |
| Orders list/create/show | /doctor/orders* | PASS |
| Results list/show | /doctor/results* | PASS (show: NOT VERIFIED — empty DB) |
| Critical alerts | /doctor/critical | PASS |
| Reports | /doctor/reports | PASS |
| Comparison | /doctor/comparison | PASS |
| Patient requests | /doctor/requests | PASS |
| Profile/Notifications | /profile, /notifications | PASS |

## Patient — all PASS
| Page | Route | Status |
|---|---|---|
| Dashboard | /patient | PASS |
| Profile/records | /patient/profile, /patient/records/{p} | PASS |
| Tests+results list | /patient/tests | PASS |
| Result detail | /patient/results/{r} | NOT VERIFIED (empty DB; status-only logic test-covered) |
| Result status | (same view branch) | PASS WITH NOTES |
| Requests | /patient/requests | PASS |
| Profile/Notifications | /profile, /notifications | PASS |

## Shared/Public
| Page | Status |
|---|---|
| /login | PASS (redesigned) |
| Error pages 403/404/419/429/500/503, errors/safe | PASS (branded) |
| /health | PASS (200) |
| mail/*, pdf/* templates | N/A (non-UI) |
| welcome | PASS WITH NOTES (unchanged Laravel page; only pre-auth landing) |

## Totals
- Distinct GET pages inventoried: ~58
- Redesigned: ~56 (welcome unchanged intentionally; mail/pdf N/A)
- HTTP-verified 200: all index/list/form pages across 4 roles
- Detail pages with real data: NOT VERIFIED (dev DB empty) — covered by view:cache + feature tests
- Functionally verified: via 121 passing automated tests
- Visually (pixel) verified: 0 — environment limitation documented
