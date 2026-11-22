# Baseline Audit — LabBI Admin UI Redesign (Phase 1)

## Repository audit (P0)

| Item | Finding |
|------|---------|
| Framework | Laravel 12 (`laravel/framework ^12.0`), PHP `^8.2` (running 8.4.26 via Herd) |
| Frontend | Blade templates only. **No Livewire, no Alpine.js.** Tailwind CSS 4 + Vite exist in `package.json` but are only used by the unused `welcome.blade.php`; the app shell does not load Vite assets |
| Styling (before) | Single shared layout `resources/views/layouts/app.blade.php` with ~170 lines of inline `<style>` serving **all roles** (admin, lab, doctor, patient, auth). Hand-rolled classes: `.shell .sidebar .card .btn .stat .badge .grid-2 .stats .flash .errors .auth-wrap` |
| JS (before) | `resources/js/app.js` (axios bootstrap) — not loaded by the app layout; effectively no frontend JS framework |
| Charts (before) | None — dashboards were static stat cards and tables |
| Icons (before) | None — text-only navigation |
| Fonts (before) | Google Fonts: IBM Plex Sans Arabic + Cairo (kept — appropriate for Arabic RTL) |
| i18n | Arabic-first (`lang="ar" dir="rtl"`); labels hardcoded in Arabic inside views (existing convention preserved) |
| Auth/RBAC | Custom `permission:` middleware + `User::hasPermission()`/`hasRole()`; role-conditional sidebar in shared layout |
| PDFs | `barryvdh/laravel-dompdf` — clinical PDF reports, untouched |
| Tests | PHPUnit 11 — 125 tests total |

## Baseline state

- Git baseline commit: `3572a2b3beeef12609a70ff2fe97d85cb91e8687` (HEAD of main at branch creation)
- Branch: `feature/admin-ui-redesign`
- Working tree before changes: clean except pre-existing untracked `package-lock.json`
- `.env`: local dev, MySQL `labbi` @ 127.0.0.1:3306, `APP_ENV=local`

## Baseline test results (before changes)

```
php artisan test → Tests: 4 failed, 121 passed (788 assertions), 26.47s
```

Pre-existing failures (unrelated to UI, present on baseline):

1. `Phase4A9QaGapTest > a9 independent metric correctness counts tat critical trend`
2. `Phase4A9QaGapTest > a9 comparison date filter falling stable and pending excluded`
3. `Phase4AcceptanceTest > ac19 doctor historical comparison and trend`
4. `Phase4AcceptanceTest > comparison incompatible units handled safely`

All four are in the doctor historical-comparison feature area — backend logic untouched by this redesign.

## Baseline routes recorded

All admin routes from `routes/web.php` (prefix `admin`, name `admin.`, middleware `permission:users.view,patients.view_all,catalog.manage,orders.view_all,bi.view` with per-route permission middleware). Full list in `ADMIN_UI_INVENTORY.md`.

## Protected backend files (verified unchanged in logic)

- `routes/web.php` — untouched
- All controllers except two **additive read-only data additions** (see FILE_CHANGE_REPORT)
- All models, migrations, services (except additive `BIQueryService` chart methods), middleware, seeders — untouched
- `database/` — zero changes

## Environment notes

- PHP CLI is not on Git Bash PATH; use `php.bat` (Herd shim) or PowerShell.
- Dev server used for verification: `php artisan serve --port=8899`
- Demo login used for verification: `admin@labbi.local` (dev seed account)
