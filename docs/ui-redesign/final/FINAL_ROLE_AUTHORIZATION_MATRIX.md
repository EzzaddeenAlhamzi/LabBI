# Final Role Authorization Matrix

Middleware unchanged (git diff routes/ = 0 files). Verified via route audit + passing ACL tests:
- Admin group: permission:users.view,patients.view_all,catalog.manage,orders.view_all,bi.view (+ per-route permissions).
- Lab group: permission:orders.view_lab,samples.receive (+ results.approve etc. per route).
- Doctor group: permission:orders.view_own_doctor,orders.create (+ results.view_own_doctor, pdf.download_clinical, comparison.use, requests.manage_doctor, critical.view_own).
- Patient group: permission:patients.view_self (+ results.view_self,orders.view_self, requests.view_own/create).
- Shared: auth middleware on all; login throttle:login.
Navigation visibility is cosmetic only; server-side permission middleware intact — no redesign bypass (no middleware/route/policy files changed).
