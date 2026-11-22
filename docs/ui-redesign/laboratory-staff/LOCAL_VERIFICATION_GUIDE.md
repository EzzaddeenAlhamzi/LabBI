# Local Verification Guide

1. git checkout feature/laboratory-staff-ui-redesign
2. php artisan serve — login lab@labbi.local / password
3. Visit /lab, /lab/orders, /lab/orders/{id}, /lab/samples, /lab/samples/reception, /lab/results/entry, /lab/results/entry/{order}, /lab/results/review, /lab/results/review/{result}, /lab/critical, /lab/critical/{alert}, /lab/attachments, /lab/completed, /lab/patients/search, /notifications, /profile
4. Check RTL, badges, charts, empty states, mobile drawer <900px
5. php artisan test — expect 120+ pass, 4 pre-existing Phase4 failures
