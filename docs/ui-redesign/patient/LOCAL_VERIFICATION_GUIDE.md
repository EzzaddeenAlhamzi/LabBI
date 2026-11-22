# Local Verification Guide

1. git checkout feature/patient-ui-redesign
2. php artisan serve — login patient@labbi.local / password
3. Visit /patient, /patient/tests, /patient/results/{id}, /patient/requests, /patient/profile, /notifications
4. Check RTL, badges, charts (own data only), status page for unpublished results, mobile drawer
5. php artisan test --filter=patient — expect 14 pass
