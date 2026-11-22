# Protected Files Verification

Verified via `git diff` against baseline `3572a2b`.

## Untouched (confirmed by empty diff)

- `routes/web.php`, `routes/console.php`
- `database/` — all migrations, seeders, factories (zero changes)
- `app/Models/` — all models
- `app/Http/Middleware/` — all middleware incl. `EnsurePermission`
- `app/Http/Controllers/` — all except the two additive Admin controllers listed below
- `app/Services/` — all except `Bi/BIQueryService.php` (additive methods only)
- `app/Domain/` — workflow/status logic
- `config/`, `bootstrap/`, `.env`, `.env.example`
- `resources/views/layouts/app.blade.php` (other roles' shell)
- `resources/views/doctor/`, `lab/`, `patient/`, `pdf/`, `mail/` — all untouched
- `tests/` — zero changes; no tests deleted or weakened
- `composer.json`, `composer.lock`, `package.json` — no dependency changes

## Touched backend files (complete list — 3)

1. `app/Services/Bi/BIQueryService.php` — +3 read-only aggregate methods
2. `app/Http/Controllers/Admin/AdminDashboardController.php` — +3 view vars
3. `app/Http/Controllers/Admin/BiDashboardController.php` — +3 view vars

No business logic, calculations, workflows, validations, or permissions were modified anywhere.
