# LabBI Admin UI Inventory

Complete inventory of every Admin-accessible page discovered from `routes/web.php`, controllers, and Blade views. Baseline commit: `3572a2b` (branch `feature/admin-ui-redesign`).

## Admin module pages (31 views / 31 GET pages)

| # | Page | Module | Route | View file | Permission | Parent | Status |
|---|------|--------|-------|-----------|------------|--------|--------|
| 1 | Admin Dashboard | Dashboard | `admin.dashboard` (GET /admin) | `admin/dashboard.blade.php` | `bi.view` | — | Redesigned ✔ Verified ✔ |
| 2 | Users list | Users | `admin.users.index` | `admin/users/index.blade.php` | `users.view` | Admin | Redesigned ✔ Verified ✔ |
| 3 | Create user | Users | `admin.users.create` | `admin/users/create.blade.php` | `users.create` | Users | Redesigned ✔ Verified ✔ |
| 4 | Edit user | Users | `admin.users.edit` | `admin/users/edit.blade.php` | `users.update` | Users | Redesigned ✔ Verified ✔ |
| 5 | User activate/deactivate | Users | `admin.users.toggle` (POST) | inline form on users index/edit | `users.activate` | Users | Preserved ✔ |
| 6 | Roles list | Roles | `admin.roles.index` | `admin/roles/index.blade.php` | `roles.manage` | Admin | Redesigned ✔ Verified ✔ |
| 7 | Role details | Roles | `admin.roles.show` | `admin/roles/show.blade.php` | `roles.manage` | Roles | Redesigned ✔ Verified ✔ |
| 8 | Permissions list | Permissions | `admin.permissions.index` | `admin/permissions/index.blade.php` | `permissions.manage` | Admin | Redesigned ✔ Verified ✔ |
| 9 | Patients list | Patients | `admin.patients.index` | `admin/patients/index.blade.php` | `patients.view_all` | Admin | Redesigned ✔ Verified ✔ |
| 10 | Create patient | Patients | `admin.patients.create` | `admin/patients/create.blade.php` | `patients.create` | Patients | Redesigned ✔ Verified ✔ |
| 11 | Patient details | Patients | `admin.patients.show` | `admin/patients/show.blade.php` | `patients.view_all` | Patients | Redesigned ✔ Verified ✔ |
| 12 | Edit patient | Patients | `admin.patients.edit` | `admin/patients/edit.blade.php` | `patients.update` | Patients | Redesigned ✔ Verified ✔ |
| 13 | Doctors list | Doctors | `admin.doctors.index` | `admin/doctors/index.blade.php` | `doctors.manage` | Admin | Redesigned ✔ Verified ✔ |
| 14 | Create doctor | Doctors | `admin.doctors.create` | `admin/doctors/create.blade.php` | `doctors.manage` | Doctors | Redesigned ✔ Verified ✔ |
| 15 | Lab staff list | Lab staff | `admin.lab-staff.index` | `admin/lab-staff/index.blade.php` | `lab_staff.manage` | Admin | Redesigned ✔ Verified ✔ |
| 16 | Create lab staff | Lab staff | `admin.lab-staff.create` | `admin/lab-staff/create.blade.php` | `lab_staff.manage` | Lab staff | Redesigned ✔ Verified ✔ |
| 17 | Departments list + create form | Departments | `admin.departments.index` / `.store` | `admin/departments/index.blade.php` | `departments.manage` | Admin | Redesigned ✔ Verified ✔ |
| 18 | Tests catalog list | Test catalog | `admin.tests.index` | `admin/tests/index.blade.php` | `catalog.view`/`catalog.manage` | Admin | Redesigned ✔ Verified ✔ |
| 19 | Create test | Test catalog | `admin.tests.create` | `admin/tests/create.blade.php` | `catalog.manage` | Tests | Redesigned ✔ Verified ✔ |
| 20 | Test details | Test catalog | `admin.tests.show` | `admin/tests/show.blade.php` | `catalog.view`/`catalog.manage` | Tests | Redesigned ✔ Verified ✔ |
| 21 | Reference ranges list + create | Ranges | `admin.ranges.index` / `.store` | `admin/ranges/index.blade.php` | `ranges.manage` | Admin | Redesigned ✔ Verified ✔ |
| 22 | Orders list | Orders | `admin.orders.index` | `admin/orders/index.blade.php` | `orders.view_all` | Admin | Redesigned ✔ Verified ✔ |
| 23 | Create order | Orders | `admin.orders.create` | `admin/orders/create.blade.php` | `orders.create` | Orders | Redesigned ✔ Verified ✔ |
| 24 | Order details | Orders | `admin.orders.show` | `admin/orders/show.blade.php` | `orders.view_all` | Orders | Redesigned ✔ (no order data in dev DB — empty-state path verified, detail markup verified via compile) |
| 25 | Deliveries & reports | Delivery | `admin.deliveries.index` | `admin/deliveries/index.blade.php` | `audit.view`/`reports.operational`/`bi.view` | Admin | Redesigned ✔ Verified ✔ |
| 26 | BI dashboard | Analytics | `admin.bi.index` | `admin/bi/dashboard.blade.php` | `bi.view` | Admin | Redesigned ✔ Verified ✔ |
| 27 | Operational report | Reports | `admin.reports.operational` | `admin/bi/operational.blade.php` | `reports.operational`/`bi.view` | Admin | Redesigned ✔ Verified ✔ |
| 28 | Critical cases | Analytics | `admin.critical-cases.index` | `admin/bi/critical.blade.php` | `bi.view` | Admin | Redesigned ✔ Verified ✔ |
| 29 | Audit log list | Audit | `admin.audit.index` | `admin/audit/index.blade.php` | `audit.view` | Admin | Redesigned ✔ Verified ✔ |
| 30 | Audit entry details | Audit | `admin.audit.show` | `admin/audit/show.blade.php` | `audit.view` | Audit | Redesigned ✔ Verified ✔ |
| 31 | Backups list | Backup | `admin.backups.index` | `admin/backups/index.blade.php` | `backups.manage` | Admin | Redesigned ✔ Verified ✔ |
| 32 | Backup details / restore | Backup | `admin.backups.show` / `.restore` | `admin/backups/show.blade.php` | `backups.manage` | Backups | Redesigned ✔ (no backup rows in dev DB — destructive section verified via code review; index verified) |
| 33 | System settings | Settings | `admin.settings.index` / `.touch` | `admin/settings/index.blade.php` | `settings.manage` | Admin | Redesigned ✔ Verified ✔ |

## Shared / secondary Admin-accessible pages

| Page | Route | View | Approach | Status |
|------|-------|------|----------|--------|
| Login | `login` | `auth/login.blade.php` | Standalone redesign with LabBI identity (shared by all roles — same page) | Redesigned ✔ Verified ✔ |
| Profile | `profile` | `profile.blade.php` | Dual markup: admin role → new design; other roles → unchanged | Redesigned (admin) ✔ |
| Notifications list | `notifications.index` | `notifications/index.blade.php` | Dual markup, same pattern | Redesigned (admin) ✔ |
| Notification details | `notifications.show` | `notifications/show.blade.php` | Dual markup, same pattern | Redesigned (admin) ✔ |
| Error pages (403/404/419/429/500/503) | — | `errors/*.blade.php` → `errors/safe.blade.php` | Shared safe error page restyled with new tokens (all roles benefit equally) | Redesigned ✔ |
| Report download | `reports.download` | PDF/file response | Not a web UI page — untouched | N/A |

## Non-GET actions preserved (no dedicated UI page)

- `admin.users.toggle`, `admin.departments.store`, `admin.ranges.store`, `admin.backups.store`, `admin.backups.restore`, `admin.settings.touch`, `notifications.read`, `notifications.read_all`, `logout` — all forms/actions preserved inside redesigned pages.

## Notes

- Permission gating is enforced server-side by the `permission:` middleware — unchanged. The sidebar shows the same role-based groups as before.
- There is no separate "user details" page (only edit) and no delete-user UI in the original app — nothing was removed.
- Doctor/Lab/Patient module pages are out of scope for this phase and were not modified.
