# Patient UI Inventory

| Module | View | Route | Type |
|---|---|---|---|
| Dashboard | patient/dashboard | patient.dashboard | dashboard + charts |
| Profile | patient/profile | patient.profile, patient.records.show | detail |
| Tests/Results | patient/tests/index | patient.tests.index | orders+results list |
| Result detail | patient/results/show | patient.results.show | approved result + PDF |
| Result status | patient/results/status | (same route, non-approved) | status page |
| Requests | patient/requests/index | patient.requests.index/store | form + history |
| Shared | notifications/*, profile | notifications.*, profile | shared |
