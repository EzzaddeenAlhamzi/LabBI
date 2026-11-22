# Functional Regression Report

## Automated tests

| Run | Result |
|-----|--------|
| Baseline (before changes) | 121 passed, **4 failed** (pre-existing Phase4 comparison failures) |
| After changes | 120 passed, **4 failed** — identical failure set; one transient view-assertion failure (`assertSee('مؤشرات تشغيلية')`) was introduced and **fixed** by restoring that phrase in the dashboard subheading |

Pre-existing failures (unchanged, backend comparison feature — out of scope):
- `Phase4A9QaGapTest` ×2, `Phase4AcceptanceTest` ×2 (historical comparison rows)

## HTTP verification (live server, admin session)

All admin GET routes returned **200**: `/admin`, `/admin/users` (+create, `/{id}/edit`), `/admin/roles` (+`/{id}`), `/admin/permissions`, `/admin/patients` (+create, `/{id}`, `/{id}/edit`), `/admin/doctors` (+create), `/admin/lab-staff` (+create), `/admin/departments`, `/admin/tests` (+create, `/{id}`), `/admin/ranges`, `/admin/orders` (+create), `/admin/deliveries`, `/admin/bi`, `/admin/reports/operational`, `/admin/critical-cases`, `/admin/audit-logs` (+`/{id}`), `/admin/backups`, `/admin/settings`, `/notifications`, `/profile`, `/login`.

`/admin/orders/{id}` and `/admin/backups/{id}` returned 404 only because the dev DB contains no order/backup rows — routes and views are correct (verified via `view:cache` compilation and identical markup on sibling pages).

## Functionality preservation checklist

- [x] All form field names, actions, methods (`PUT` via `@method`), `@csrf` preserved verbatim
- [x] `old()` input retention preserved on all forms
- [x] Filter query params (`q`, `role`, `user_id`, `action`, `entity`, `date_from`, `date_to`, `test_id`, `doctor_id`, `department_id`, `status`, `patient_id`) preserved
- [x] Pagination preserved via custom view (`->links('admin.partials.pagination')`)
- [x] Destructive restore flow preserved: acknowledge checkbox + typed `RESTORE` confirmation + server-side validation
- [x] User activate/deactivate POST form preserved
- [x] Settings "touch" audit POST preserved
- [x] Report download links (`reports.download`) preserved
- [x] Notification mark-read/read-all preserved
- [x] Logout POST preserved (topbar + sidebar footer)
- [x] Permission middleware untouched — server-side enforcement identical
- [x] No N+1 introduced: new chart queries are 3 aggregate queries per dashboard load

## Known limitations

- Dev DB has sparse data: orders/backups tables empty → those detail pages verified by compilation + pattern, not live render
- No prior-period comparison data exists → no trend sparklines/% changes shown (deliberate — no fake metrics)
- `package-lock.json` was already untracked before this work; left uncommitted
