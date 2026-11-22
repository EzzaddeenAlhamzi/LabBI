# Admin Dashboard Design

## Data sources (all real, read-only)

- `BIQueryService::dashboard()` → 12 KPI cards (patients, doctors, catalog tests, orders today, samples awaiting, in-analysis, pending-review, ready/completed, deferred, rejected, critical cases, critical open)
- `BIQueryService::performanceMetrics()` → orders/tests/critical in period, avg order TAT hours, avg critical handling hours, limitations list
- **New additive methods** (read-only `selectRaw` aggregations, documented in FILE_CHANGE_REPORT):
  - `ordersDailySeries()` — orders per day, last 14 days (filter-aware on BI page)
  - `statusBreakdown()` — order counts grouped by status (filter-aware)
  - `departmentWorkload()` — test-order items per department (top 8)

## Layout

1. **Header**: title + subtitle + actions (BI, operational report, new order)
2. **KPI grid**: 12 cards, auto-fit ~4/row on desktop, icon chip + label + large value; tone-coded icons (red reserved for critical metrics)
3. **Main analytics row** (2fr/1fr): area chart "أداء الطلبات — آخر 14 يومًا" + donut "توزيع حالات الطلبات" with center total
4. **Secondary row**: horizontal bars "عبء العمل حسب القسم" + "مؤشرات الأداء" card (period metrics + limitations)

## BI dashboard (`admin/bi`)

Same KPI/chart system plus the full existing filter panel (date range, test, doctor, department, status). Charts respect active filters via `BiReportFilters`. TAT table preserved verbatim including limitations notes.

## Operational report (`admin/reports/operational`)

KPI cards + status donut (same `statusVolume` server metrics as the table — no duplicate calculation) + status table + orders detail table. All existing filters preserved.

## Critical cases (`admin/critical-cases`)

KPI cards (total/open/acknowledged/closed/avg handling) + full alert table with semantic status badges; critical values rendered in danger color with units.

## Honesty rules enforced

- No fabricated trends, percentages, or sparklines — none are shown because no prior-period comparison data exists
- TAT shows "غير متاح" when timestamps are missing (existing limitation strings preserved)
- Empty chart data renders an explicit empty state, not fake data
