# Design System Reuse

Phase 2 reuses 100% of the Phase 1 system — no second identity created:

- Layout: `layouts/admin.blade.php` (role-aware sidebar already scopes lab_technician to lab modules only; admin sees both groups).
- CSS: `public/css/admin.css` — kpi-grid/kpi, card(+flush), card-title/card-sub, table.data, table-wrap, pager, dl, form-grid/field/form-actions, alert-*, badge-*, empty/empty-icon, grid-2/3/4, tone-* icons, muted-cell, small, text-end, row-actions.
- Components: `x-icon` (~70 SVG icons), `x-status-badge` (auto tone map for order/sample/result/critical statuses), `admin.partials.pagination`.
- Charts: `LabBICharts` (admin-charts.js) — area + donut on lab dashboard fed by real BIQueryService aggregates.
- RTL: dir="ltr" applied to codes, units, timestamps, medical file numbers.
