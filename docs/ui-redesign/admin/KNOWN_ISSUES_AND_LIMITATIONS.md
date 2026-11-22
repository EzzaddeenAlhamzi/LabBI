# Known Issues & Limitations

1. **Sparse dev data**: The local `labbi` DB has no `test_orders`/`backups` rows, so `admin/orders/{id}` and `admin/backups/{id}` detail pages could not be rendered live. They compile cleanly and reuse verified components; recommend a visual check on an environment with data.
2. **No trend/sparkline metrics**: The system does not store prior-period aggregates, so KPI cards show current values only — no fabricated % changes (per spec).
3. **Chart data window**: Dashboard charts cover the last 14 days of orders; longer history is available via the BI/operational report date filters.
4. **Pagination**: Admin pages use a custom self-contained pagination view; other roles still use the stock Tailwind paginator (unstyled, as before — unchanged behavior).
5. **Shared pages**: Profile/Notifications render the new design only for `admin` role users; doctor/lab/patient see the previous UI (their dedicated phases pending PO approval). Login and error pages are shared and now use the new identity for all roles.
6. **Google Fonts**: Font loading still uses the Google Fonts CDN (as the original app did). For fully offline environments, self-hosting IBM Plex Sans Arabic + Cairo is recommended (documented, not implemented to avoid new binary assets).
7. **Pre-existing test failures**: 4 backend test failures in the Phase4 historical-comparison feature exist on the baseline commit and are unrelated to this redesign.
8. **`package-lock.json`** was untracked before this work and remains uncommitted.
