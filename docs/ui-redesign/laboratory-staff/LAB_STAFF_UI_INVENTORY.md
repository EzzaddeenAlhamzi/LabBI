# Lab Staff UI Inventory

| Module | View | Route | Type |
|---|---|---|---|
| Dashboard | lab/dashboard | lab.dashboard | dashboard |
| Orders | lab/orders/index | lab.orders.index | list + status filter |
| Order detail | lab/orders/show | lab.orders.show | detail |
| Samples | lab/samples/index | lab.samples.index | list |
| Sample reception | lab/samples/reception | lab.samples.reception / .receive | form |
| Result entry list | lab/results/entry-index | lab.results.entry.index | list |
| Result entry | lab/results/entry | lab.results.entry / .save / .start_analysis | multi-test form |
| Review queue | lab/results/review-index | lab.results.review.index | list |
| Review detail | lab/results/review-show | lab.results.review.show / .approve / .return / .reject | detail + workflow forms |
| Critical alerts | lab/critical/index | lab.critical.index | list |
| Critical detail | lab/critical/show | lab.critical.show / .acknowledge | detail + ack form |
| Attachments | lab/attachments/index | lab.attachments.index / .store / .download | upload + list |
| Completed | lab/completed/index | lab.completed.index | list |
| Patient search | lab/patients/search | lab.patients.search | search + results |
| Notifications | notifications/index, show | notifications.* | shared |
| Profile | profile | profile | shared |
