# Functional Regression Report

`php artisan test`: 120 passed / 5 failed → after fix: the only new failure (ordersDailySeries arg type in LabDashboardController) was corrected; remaining 4 failures are the pre-existing Phase4 historical-comparison failures identical to the Phase 1 baseline (HistoricalComparisonService returns 0 rows; avg_order_tat_seconds null). Zero regressions attributable to the redesign.

`php artisan view:cache`: all Blade templates compile.
