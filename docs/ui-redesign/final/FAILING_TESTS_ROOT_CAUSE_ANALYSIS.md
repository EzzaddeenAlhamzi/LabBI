# Failing Tests Root Cause Analysis

Suite result (final branch): 121 passed, 4 failed, 788 assertions, ~41s.

Failures — all HistoricalComparisonService consumers:
1. Phase4A9QaGapTest > a9 independent metric correctness counts tat critical trend
2. Phase4A9QaGapTest > a9 comparison date filter falling stable and pending excluded
3. Phase4AcceptanceTest > ac19 doctor historical comparison and trend
4. Phase4AcceptanceTest > comparison incompatible units handled safely

Evidence: `git diff 3572a2b..HEAD` touches zero files under app/Services except Bi/BIQueryService.php (additive read methods only). HistoricalComparisonService, Result model, range logic, factories and all comparison tests are byte-identical to baseline. Same 4 failures appeared at the Phase 1 baseline before redesign scope expanded. Signature: compare()['rows'] returns 0 rows where fixtures expect 2 — a service/fixture logic issue unrelated to presentation.

Classification: PRE-EXISTING defect. NOT a redesign regression. Fixing requires PO approval (protected clinical-comparison logic). Not fixed in Phase 5.
