# Doctor UI Inventory

| Module | View | Route | Type |
|---|---|---|---|
| Dashboard | doctor/dashboard | doctor.dashboard | dashboard + charts |
| Patients | doctor/patients/index | doctor.patients.index | list |
| Patient detail | doctor/patients/show | doctor.patients.show | clinical record + orders |
| Orders | doctor/orders/index | doctor.orders.index | list |
| Order create | doctor/orders/create | doctor.orders.create/store | form + test selection |
| Order detail | doctor/orders/show | doctor.orders.show | detail (approved results only) |
| Results | doctor/results/index | doctor.results.index | approved results list |
| Result detail | doctor/results/show | doctor.results.show | clinical result + PDF |
| Reports | doctor/reports/index | doctor.reports.index | PDF list + download |
| Requests | doctor/requests/index | doctor.requests.* | patient requests workflow |
| Critical | doctor/critical/index | doctor.critical.index | critical alerts list |
| Comparison | doctor/comparison/index | doctor.comparison.index | historical comparison |
| Shared | notifications/*, profile | notifications.*, profile | shared |
