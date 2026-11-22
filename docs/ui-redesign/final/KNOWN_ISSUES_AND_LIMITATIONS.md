# Known Issues and Limitations

1. 4 pre-existing test failures in HistoricalComparisonService consumers (see FAILING_TESTS_ROOT_CAUSE_ANALYSIS.md) — unrelated to redesign; requires PO decision on protected comparison logic.
2. No pixel/browser-automation verification or screenshots — environment lacks Playwright; verification was HTTP-level + compilation + automated tests. PO should do visual pass locally (see LOCAL_SETUP_AND_VERIFICATION_GUIDE.md).
3. Dev database empty (0 orders) — detail pages verified via empty states, compilation, and feature tests, not populated-data rendering.
4. Favicon unchanged from original.
5. Shared views retain unreachable legacy markup branch for hypothetical unmapped roles — harmless dead code, kept to avoid removing functionality.
6. Formal WCAG 2.2 AA audit not performed — accessibility verified by code inspection only.
