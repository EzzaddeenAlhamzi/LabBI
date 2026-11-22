# Page Coverage Matrix

| Module | Index | Create | Edit | Show/Details | Secondary/Actions | Verified |
|--------|-------|--------|------|--------------|-------------------|----------|
| Dashboard | ✔ | — | — | — | page actions (BI/report/new order) | HTTP 200 + markup |
| Users | ✔ | ✔ | ✔ | — (none exists) | toggle activate/deactivate form | HTTP 200 |
| Roles | ✔ | — (none) | — | ✔ | — | HTTP 200 |
| Permissions | ✔ | — | — | — | — | HTTP 200 |
| Patients | ✔ | ✔ | ✔ | ✔ (orders relation) | search filter | HTTP 200 |
| Doctors | ✔ | ✔ | — (none) | — | — | HTTP 200 |
| Lab staff | ✔ | ✔ | — (none) | — | — | HTTP 200 |
| Departments | ✔ (+inline create) | inline | — | — | store POST | HTTP 200 |
| Test catalog | ✔ | ✔ | — (none) | ✔ (ranges relation) | link to ranges | HTTP 200 |
| Ranges | ✔ (+inline create) | inline | — | — | store POST | HTTP 200 |
| Orders | ✔ | ✔ | — (none) | ✔ (items+samples) | search filter | index HTTP 200; show compiled (no seed orders) |
| Deliveries | ✔ | — | — | — | report download links | HTTP 200 |
| BI | ✔ dashboard | — | — | — | filters | HTTP 200 |
| Operational reports | ✔ | — | — | — | filters, pagination | HTTP 200 |
| Critical cases | ✔ | — | — | — | filters, pagination | HTTP 200 |
| Audit | ✔ | — | — | ✔ (old/new JSON) | filters | HTTP 200 |
| Backups | ✔ | — (manual trigger POST) | — | ✔ (manifest + destructive restore) | restore confirm | index HTTP 200; show compiled |
| Settings | ✔ | — | — | — | settings.touch POST | HTTP 200 |
| Shared: Login | ✔ | — | — | — | — | HTTP 200 |
| Shared: Profile | ✔ | — | — | — | — | HTTP 200 (admin branch) |
| Shared: Notifications | ✔ | — | — | ✔ | mark-read/read-all | HTTP 200 (admin branch) |
| Errors | ✔ | — | — | — | — | compiled |

**Coverage: 33 admin page views — 100% redesigned.** Pages that could not be rendered with live data in dev (order detail, backup detail — tables empty in dev DB) were verified via Blade compilation, code review, and identical markup patterns proven on sibling pages.
