# LabBI — Demo Seeding Security Review (A9)

**Status:** Independent A9 review complete  
**Verdict:** **PASS WITH NOTES**  
**Reviewer role:** A9 (independent)  
**Date:** 2026-11-16  
**Scope:** `LabBIDemoSeeder`, `App\Services\Demo\LabBIDemoSeedingService`, demo config, related tests/docs  
**Code under review:** branch `cursor/demo-seeding-system-ecb4` (uncommitted worktree)  
**Related:** A8 medium closure (PR #15); Project Context internal detail `internal/demo-seeding-a9-security.md`

---

## Checklist (required)

| # | Control | Result | Evidence |
|---|---------|--------|----------|
| 1 | Refuses `APP_ENV=production` | **PASS** | Dual guard: seeder + service throw `RuntimeException`. Covered by `LabBIDemoSeederTest::test_refuses_production_environment`. |
| 2 | No real email / SMS / WhatsApp during seed | **PASS** | `Mail::fake()` at service start. No `Mail::send` / SMS client. WhatsApp rows are DB stubs (`provider_ref=mock-wa-demo`); gateway not invoked. No SMS channel in seeder. |
| 3 | No real PHI / no invented medical claims as validated | **PASS** | Synthetic `demo.*@labbi.local`, `MF-DEMO…`, null `national_id`. Result/order notes + catalog comments label **CAT-04 demo-only** (not clinical authority). |
| 4 | No password/secret leakage in logs/reports | **PASS** | Console prints counts/profile/seed only — not hashes or plaintext passwords. Docs list well-known demo password `password` (same as existing `LOCAL_DEPLOYMENT` canonical demos). No `APP_KEY` / DB / mail secrets in seed output. |
| 5 | No `migrate:fresh` / truncate / destructive global cleanup | **PASS** | Purge is namespace-scoped (`ORD-{ns}-%`, `MF-{ns}-%`, `demo.*.{ns}.*@labbi.local`, `demo_pdf:{ns}:%`, `D{seed}T%` tests). No truncate-all / migrate:fresh. |
| 6 | Idempotency / synthetic-only ownership safe | **PASS** | Re-run purges then recreates same namespace. Canonical `*@labbi.local` / `MF-000001` / `HB`/`GLU` preserved via `firstOrCreate` (passwords not overwritten). Tested. |
| 7 | Private storage / PDF paths safe | **PASS WITH NOTES** | Writes `Storage::disk('local')` → `storage/app/private/reports/demo/{ns}/{uuid}.pdf`. Downloads gated by `PdfReportService::assertCanDownload` / `ReportDownloadController`. |
| 8 | Not auto-wired into `DatabaseSeeder` (LARGE risk) | **PASS** | `DatabaseSeeder` calls only `DemoDataSeeder`; explicit comment forbids volume seeder. Test asserts zero `ORD-DEMO%` after default seed. |

---

## Notes (non-blocking)

1. **Orphan PDF files:** Purge deletes `generated_reports` rows for the namespace but does **not** delete corresponding files under `storage/app/private/reports/demo/…`. Low sensitivity (minimal PDF bytes, UUID names) but disk can accumulate on repeated LARGE runs. Prefer deleting storage objects on purge in a follow-up.
2. **`local` disk `serve => true`:** Laravel local disk allows framework serving; demo PDFs remain non-public paths and app download is RBAC-gated. Do not `storage:link` private roots.
3. **Env string exactness:** Guard checks `environment('production')` only — custom aliases (e.g. `prod`) are not covered. Ops must keep real production as `APP_ENV=production`.
4. **Ops runbook gap:** `PRODUCTION_DEPLOYMENT.md` warns against blind re-seed but does not name `LabBIDemoSeeder`. Add an explicit “never run volume demo seeder in production” line when docs are next edited.
5. **Well-known demo password:** Synthetic + canonical users use `password` by design (pre-existing LabBI demo convention). Acceptable for local/staging only; never reuse on production accounts.

---

## Residual asks (ops / PO)

- Confirm staging may retain MEDIUM/LARGE synthetic datasets.  
- Optional schema `is_demo` flag remains **proposal only** (not required for this verdict).  
- CAT-04 medical sign-off (LB-2) remains open — demo ranges must stay labeled until then.

---

## Verdict

**PASS WITH NOTES** — safe to proceed with PO-approved commit/push for development/staging demo seeding. No FAIL findings on the eight required controls. Address notes (esp. PDF file cleanup + production runbook wording) as follow-ups, not blockers for merge of the seeder itself.
