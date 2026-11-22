# Security Review

- No authorization changes: routes/middleware/permissions untouched; `results.approve` gate preserved in review-show; lab nav visibility unchanged.
- No new data exposure: dashboard charts reuse BIQueryService aggregates already visible to the role; no patient-level drilldown added.
- CSRF, validation, output escaping, audit logging untouched; no secrets committed.
