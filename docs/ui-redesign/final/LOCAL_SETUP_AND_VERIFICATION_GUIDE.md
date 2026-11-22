# Local Setup & Verification Guide (Windows / Herd / PHP 8.4 / MySQL 8)

```powershell
git fetch origin
git checkout feature/final-ui-ux-quality-assurance
# keep existing .env and APP_KEY — do NOT regenerate
composer install
npm install        # only if building assets; redesign CSS is in public/
php artisan migrate:status   # verify, do NOT migrate:fresh
php artisan storage:link     # if not already linked
php artisan view:clear; php artisan view:cache
php artisan serve
```

Test accounts (local dev seed credentials — NOT for production):
admin@labbi.local / doctor@labbi.local / lab@labbi.local / patient@labbi.local — password: `password`

Run tests (isolated test DB): `php artisan test` — expect 121 passed / 4 pre-existing comparison failures.

Manual visual checklist: log in per role; visit dashboard, lists, one create form, one detail page; resize to 768px/390px; toggle language if available.
