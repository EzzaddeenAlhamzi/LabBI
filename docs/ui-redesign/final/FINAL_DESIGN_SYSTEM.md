# Final Design System

Single source of truth: `public/css/admin.css` + `resources/views/components/{icon,status-badge}.blade.php` + `public/js/{admin.js,admin-charts.js}`.

Tokens: navy #142B45, teal #0D9488, hover #0F766E, light #CCFBF1, blue #2563EB, purple #7C3AED, cyan #0EA5E9, bg #F5F7FB, surface #FFF, text #172B4D/#64748B, border #E2E8F0, success #16A34A, warning #D97706, danger #DC2626 — all present in admin.css.

Component classes verified in use across all roles: kpi-grid/kpi-*, card(.flush), table-wrap/table.data, pager/pagination, form-grid/field/input/form-actions, dl, empty/empty-icon, alert, badge, btn, row-actions, grid-2/3/4, tone-* palette. No competing design system introduced; no CSS removed.
