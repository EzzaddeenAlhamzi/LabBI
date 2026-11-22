# Final Backend Change Register

Complete register of non-view changes vs baseline 3572a2b:

| File | Change | Classification |
|---|---|---|
| app/Services/Bi/BIQueryService.php | +ordersDailySeries, +statusBreakdown, +departmentWorkload (read-only GROUP BY aggregates honoring BiReportFilters) | Additive read-only analytics |
| app/Http/Controllers/Admin/AdminDashboardController.php | passes 3 new series to view | Presentation-only |
| app/Http/Controllers/Admin/BiDashboardController.php | passes 3 new series; import cleanup FQCN->import | Presentation-only |
| app/Http/Controllers/Doctor/DoctorDashboardController.php | doctor_id-scoped series/KPIs | Additive read-only analytics (scoped) |
| app/Http/Controllers/Lab/LabDashboardController.php | series/statusBreakdown; removed 2 unused imports | Additive read-only analytics |
| app/Http/Controllers/Patient/PatientDashboardController.php | patient_id-scoped series, approved-result count, recent orders | Additive read-only analytics (scoped) |

No writes, no workflow changes, no auth changes, no clinical-calculation changes. All approved within redesign scope (presentation data provisioning). No unauthorized or suspicious changes found.
