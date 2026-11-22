# Final System UI Inventory

## Admin (28 GET pages)
/admin dashboard, /admin/users (+create), /admin/roles (+show), /admin/permissions, /admin/patients (+create/show/edit), /admin/doctors (+create), /admin/lab-staff (+create), /admin/departments, /admin/tests (+create/show), /admin/ranges, /admin/orders (+create/show), /admin/audit-logs (+show), /admin/backups (+show), /admin/settings, /admin/deliveries, /admin/reports/operational, /admin/critical-cases, /admin/bi, /profile, /notifications

## Laboratory Staff (12)
/lab dashboard, /lab/orders (+show), /lab/samples, /lab/samples/reception, /lab/patients/search, /lab/results/entry (+per-order), /lab/results/review (+per-result), /lab/completed, /lab/critical (+show), /lab/attachments, /profile, /notifications

## Doctor (11)
/doctor dashboard, /doctor/patients (+show), /doctor/orders (+create/show), /doctor/results (+show), /doctor/critical, /doctor/reports, /doctor/comparison, /doctor/requests, /profile, /notifications

## Patient (7)
/patient dashboard, /patient/profile, /patient/records/{patient}, /patient/tests, /patient/results/{result} (+status), /patient/requests, /profile, /notifications

## Shared/Public
/login, /logout (POST), /health, error pages 403/404/419/429/500/503 + errors/safe, / (redirect), welcome, mail + PDF templates (non-HTML-UI).

Total distinct user-facing pages: ~58 GET interfaces + workflow POST actions (not counted as pages).
