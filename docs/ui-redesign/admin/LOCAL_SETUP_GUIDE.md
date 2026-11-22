# Local Setup Guide — LabBI (Windows / Laravel Herd)

## Requirements

- Windows + Laravel Herd (PHP 8.4 provided), Composer, MySQL 8, Node.js (only needed for Vite — **not required for the Admin UI**)

## Setup

```powershell
cd E:\Project\LabBI\LabBI
composer install
copy .env.example .env   # only if .env missing — DO NOT overwrite existing .env
php artisan key:generate # only for fresh installs
php artisan migrate
php artisan db:seed     # dev seed accounts (admin@labbi.local / password, etc.)
php artisan serve
```

Open `http://127.0.0.1:8000` → login.

## Admin UI assets

The Admin design system uses **static assets — no build step**:

- `public/css/admin.css`
- `public/js/admin.js`
- `public/js/admin-charts.js`

They are served directly by Laravel (`asset()`). `npm run build` / `npm run dev` is only needed for `welcome.blade.php` (unused) — safe to skip.

## Useful commands

```powershell
php artisan test          # run test suite
php artisan view:clear    # clear compiled Blade views after view edits
php artisan view:cache    # verify all Blade templates compile
php artisan serve --port=8899
```

Note: in Git Bash, `php` may not resolve — use `php.bat` or PowerShell.

## Demo accounts (dev seed)

`admin@labbi.local`, `doctor@labbi.local`, `lab@labbi.local`, `patient@labbi.local` — password `password` (local dev only).
