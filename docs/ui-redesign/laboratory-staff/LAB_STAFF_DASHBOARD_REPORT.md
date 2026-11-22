# Lab Staff Dashboard Report

- 8 KPI cards (kpi-grid): new orders, awaiting sample, in analysis, pending review, ready, deferred, rejected, open critical alerts — all real counts from existing controller queries.
- 14-day orders area chart + 30-day status donut via `BIQueryService::ordersDailySeries` / `statusBreakdown` (additive read-only; bounded BiReportFilters windows).
- Quick actions card linking to reception, new orders, result entry, review, critical, attachments, patient search.
- Recent active orders table (requested→pending_review) with status badges and order links.
- Deliberately operational (work queues) not a copy of the admin BI dashboard.
