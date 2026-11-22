# File Change Report

Branch: `feature/admin-ui-redesign` · Baseline: `3572a2b`

## New files

| File | Purpose |
|------|---------|
| `public/css/admin.css` | Complete Admin design system (~500 lines, design tokens + all components) |
| `public/js/admin.js` | Sidebar collapse/drawer, dropdowns, Escape handling — no dependencies |
| `public/js/admin-charts.js` | Dependency-free SVG chart renderer (area/donut/bars/sparkline + tooltip) |
| `resources/views/layouts/admin.blade.php` | New Admin application shell (sidebar/topbar/content), isolated from other roles |
| `resources/views/components/icon.blade.php` | `<x-icon>` inline SVG icon set (~55 icons) |
| `resources/views/components/status-badge.blade.php` | `<x-status-badge>` semantic status badge with status→tone map |
| `resources/views/admin/partials/pagination.blade.php` | Self-contained styled pagination view |
| `docs/ui-redesign/admin/*.md` | Required documentation set |

## Modified files — backend (3, all additive read-only presentation data)

| File | Change | Risk |
|------|--------|------|
| `app/Services/Bi/BIQueryService.php` | Added `ordersDailySeries()`, `statusBreakdown()`, `departmentWorkload()` — pure `selectRaw` read aggregations for charts; no schema, workflow, or calculation changes | Low — read-only |
| `app/Http/Controllers/Admin/AdminDashboardController.php` | Passes 3 new chart datasets to the view | Low |
| `app/Http/Controllers/Admin/BiDashboardController.php` | Same, filter-aware | Low |

**Zero changes** to: routes, migrations, models, middleware, other services, other controllers, seeders, config, `.env`.

## Modified files — views (39)

- All 31 `resources/views/admin/**` views → `@extends('layouts.admin')` + full markup redesign (every field name, form action, HTTP method, CSRF token, `old()` binding, filter param, and action preserved)
- `resources/views/auth/login.blade.php` → standalone branded login (shared page, same for all roles)
- `resources/views/errors/safe.blade.php` → restyled error page (shared, benefits all roles)
- `resources/views/profile.blade.php`, `resources/views/notifications/index.blade.php`, `resources/views/notifications/show.blade.php` → dual markup: admin role gets new design, other roles get the original markup unchanged

## Unchanged (verified)

- `resources/views/layouts/app.blade.php` — other roles' shell untouched
- All `doctor/`, `lab/`, `patient/`, `pdf/`, `mail/` views — untouched
- `routes/web.php`, `app/Http/Middleware`, `app/Models`, `database/` — untouched

## Dependency changes

None. No new npm/composer packages. Charts and icons are hand-rolled SVG/vanilla JS to keep the app lightweight and avoid a frontend build step.
