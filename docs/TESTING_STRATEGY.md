# LabBI — Testing Strategy

**Status:** Phase 0 planning (not implemented)

---

## 1. Goals

Prove every `REQUIREMENTS_MATRIX.md` row and especially **AC-01…AC-25** before calling a phase done. Prefer automated tests for engines/RBAC; use E2E for cross-role journeys.

---

## 2. Test Levels

| Level | Scope | Owners |
|---|---|---|
| Unit | Critical engine classification, TAT math, trend direction, validators, permission helpers | A4, A7, A1 |
| Integration | DB repositories, workflow transitions, notification job enqueue, PDF generation hooks | A3, A4, A5 |
| API | REST contracts, authz allow/deny, validation errors | A1, A9 |
| E2E / UI | Portal journeys in Arabic RTL | A6, A9 |
| Security | XSS/SQLi smoke, session flags, rate limit, IDOR | A8, A9 |
| Ops | Backup job + restore drill | A8, A9 |

---

## 3. Acceptance Criteria Mapping

| AC | Primary test type | Phase |
|---|---|---|
| AC-01 | E2E admin creates doctor/tech/patient + permissions | P1 |
| AC-02 | API/E2E IDOR: patient A denied patient B | P1 |
| AC-03 | Integration unique medical ID | P1 |
| AC-04 | E2E create order links | P1 |
| AC-05 | E2E lab queue visibility | P1 |
| AC-06 | E2E sample receive | P1 |
| AC-07 | E2E result entry | P2 |
| AC-08 | Unit + E2E normal → no critical alert | P2 |
| AC-09 | Unit + E2E over critical → detect | P2 |
| AC-10 | E2E alert visible immediately | P2 |
| AC-11 | Integration notification to doctor | P2–P3 |
| AC-12 | DB assertions detected_at / notified_at | P2–P3 |
| AC-13 | E2E approve | P2 |
| AC-14 | E2E PDF generate | P3 |
| AC-15 | E2E doctor access | P3 |
| AC-16 | Integration mail fake/assert | P3 |
| AC-17 | Integration WhatsApp mock + patient notify | P3 |
| AC-18 | E2E archive presence | P2–P3 |
| AC-19 | E2E comparison | P4 |
| AC-20 | E2E/API BI counts & times | P4 |
| AC-21 | Authz suite across roles | P1+ |
| AC-22 | Ops scheduled backup | P5 |
| AC-23 | Ops restore | P5 |
| AC-24 | UI locale QA Arabic | P1+ |
| AC-25 | Cross-browser checklist | P5 |

---

## 4. Critical Engine Fixtures (mandatory)

Maintain a clinical fixture table (synthetic, non-PHI):

| Test code | Normal | Critical | Inputs → expected class |
|---|---|---|---|
| Example Hb | 12–16 | <7 or >20 | boundary matrix |

Every range change in catalog must update fixtures. Engine tests must use **persisted ranges**, not hardcoded expected magic in production code.

---

## 5. RBAC / Security Suites

- Matrix-driven tests: each role × representative endpoints → allow/deny  
- Patient scope tests (AC-02)  
- Inactive user login denial  
- CSRF on state-changing cookie routes  
- Upload MIME/size rejection  

---

## 6. Non-Functional Testing

| NFR | Approach |
|---|---|
| Performance | Smoke timings for search, order list, BI summary — **no invented hard SLA** until agreed |
| Availability 99% | Post-deploy monitoring (P5), not a unit test |
| Backup | Monthly restore drill in staging |
| Browsers | Chrome, Firefox, Safari/Edge latest — confirm target list with user |

---

## 7. Test Data & Environments

- `testing` DB refreshed per CI run  
- Factories for users/roles/patients/orders/results  
- Never use real patient PHI in fixtures  
- Mail/WhatsApp providers mocked in CI  

---

## 8. Definition of Done per Phase

Phase exits only when:

1. Automated tests for that phase’s AC IDs are green in CI  
2. Manual exploratory pass on Arabic UI for new screens  
3. No open Sev-1 security findings on new code  
4. `PROJECT_PROGRESS.md` updated (status remains not implemented until code exists; then `in progress`/`done` for tasks — requirements stay until verified)

---

## 9. Tools (recommended, not locked)

PHPUnit / Pest · HTTP feature tests · optional Playwright/Cypress for E2E · PHPStan/Psalm static analysis · Magento-like not applicable.

Lock tooling with framework decision in `DECISIONS.md`.
