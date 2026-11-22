# Design System Reuse

100% reuse of Phase 1/2 system: layouts/admin.blade.php (doctor nav group added, role-scoped), public/css/admin.css (kpi-grid, card.flush, table.data, pager, dl, form-grid, alert-*, badge-*, empty, grid-*, tone-*), x-icon, x-status-badge, admin.partials.pagination, LabBICharts.

Doctor dashboard charts reuse BIQueryService::ordersDailySeries/statusBreakdown scoped by doctorId — read-only, doctor-authorized aggregates only.
