# Local Verification Guide

1. git checkout feature/doctor-ui-redesign
2. php artisan serve — login doctor@labbi.local / password
3. Visit /doctor, /doctor/patients, /doctor/patients/{id}, /doctor/orders, /doctor/orders/create, /doctor/orders/{id}, /doctor/results, /doctor/results/{id}, /doctor/reports, /doctor/requests, /doctor/critical, /doctor/comparison, /notifications, /profile
4. Check RTL, badges, charts, approved-only results, empty states, mobile drawer
5. php artisan test — expect suite pass except 4 pre-existing Phase4 failures
