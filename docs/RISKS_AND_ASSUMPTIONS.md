# LabBI — Risks and Assumptions

**Status:** Phase 1 — assumptions updated for locked decisions

---

## 1. Assumptions (planning defaults)

| ID | Assumption | If wrong |
|---|---|---|
| A-01 | v1 is single laboratory (departments optional), not multi-tenant SaaS | Need `laboratory_id` tenancy earlier |
| A-02 | Manual result entry only (analyzer = Future) — **locked D-006** | Need analyzer adapter + device specs |
| A-03 | Four primary roles only — **no Receptionist in V1 (D-009)** | Add receptionist/supervisor roles later |
| A-04 | Arabic UI first; English optional later | Dual-language UI from P1 |
| A-05 | Structured patient requests instead of chat | Build messaging subsystem |
| A-06 | Laravel is the PHP framework — **locked D-003** | Costly re-plan after P1 |
| A-07 | Clinical reference/critical ranges will be provided by medical authority before go-live | Critical engine cannot be clinically valid |
| A-08 | Email SMTP and WhatsApp credentials will be available before P3 exit | Mock forever blocks AC-16/17 in prod |
| A-09 | Academic cost figures in source are irrelevant to engineering | N/A |
| A-10 | Blank source page 20 contained no requirements | Re-check if new DOCX version appears |
| A-11 | Result approval V1 = Admin only via `results.approve` permission (D-008 / REV-05) | Reassign permission grants; avoid role-name hardcoding |

---

## 2. Contradictions in Source (resolved in planning)

| ID | Contradiction | Resolution |
|---|---|---|
| C-01 | Order statuses differ across sections | Unified workflow D-005 |
| C-02 | Analyzer integration “now” vs SWOT “future” | **Locked Future** / manual (D-006) |
| C-03 | Doctor feature list omits create-order; lab text requires doctor orders | Mandate doctor create-order (D-007) |
| C-04 | Password guidance cites SHA-256 and bcrypt | Prefer bcrypt/Argon2 for passwords (D-012) |
| C-05 | Marketing personas vs four functional roles | Keep four roles; no Receptionist (D-009) |

---

## 3. Requirement Gaps (from source §47)

| Gap | Risk if ignored | Mitigation |
|---|---|---|
| GAP-01 Doctor create order | Broken intake | Required in P1 |
| GAP-02 Ranges governance | Wrong critical alerts | CAT-04 medical sign-off |
| GAP-03 Approver undefined | Unsafe or blocked P2 | **Resolved D-008:** Admin via permission |
| GAP-04 Status conflict | Inconsistent UI/BI | D-005 |
| GAP-05 Receptionist | Operational mismatch | **Resolved D-009:** no fifth role |
| GAP-06 WhatsApp provider | P3 blockage | Interface + decision |
| GAP-07 Analyzer | Scope creep / blocked integration | **Resolved D-006:** Future |
| GAP-08 Patient–doctor comms | Wrong UX build | D-010 |
| GAP-09 PDF template | Unusable clinical reports | Stakeholder assets |
| GAP-10 Retention | Compliance risk | Policy before prod |
| GAP-11 Password recovery | Support burden / lockouts | D-018 |
| GAP-12 No ERD | Implementation ambiguity | `DATABASE_DESIGN.md` |
| GAP-13 No wireframes | UI rework | Wireframe milestone in P1 |
| GAP-14 Empty timeline | Unclear sequencing | `IMPLEMENTATION_ROADMAP.md` |
| GAP-16 Toy budget numbers | False expectations | Ignore for planning |

---

## 4. Major Project Risks

| ID | Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|---|
| R-01 | Clinical ranges incorrect or missing | Critical alerts wrong → patient harm / liability | High until sign-off | Block prod on medical catalog approval |
| R-02 | WhatsApp/email delivery unreliable | Missed critical communication | Medium | Retries, in-app fallback, delivery logs, escalation SOP |
| R-03 | Scope creep (inventory/AI/mobile/analyzer) | Delays core AC | Medium | Hard OUT-* gate; analyzer Future |
| R-04 | PHI breach | Severe | Medium without hardening | SECURITY_PLAN P1–P5 |
| R-05 | Framework lock delayed | Idle parallel agents | **Mitigated** | D-003 locked Laravel |
| R-06 | Approver politics | Workflow thrash | **Mitigated for V1** | D-008 Admin permission; may revisit later |
| R-07 | Performance of BI on raw SQL | Slow admin dashboards | Low–Med | Indexes now; summaries later |
| R-08 | Hosting undecided (local vs cloud) | Late infra scramble | Medium | Portable design; decide by P5 |
| R-09 | No retention/legal review | Non-compliance | Unknown | Stakeholder legal input |
| R-10 | Parallel agents conflicting | Merge hell | Medium | Ownership matrix A0–A9; P1 single-branch OK |
| R-11 | Treating academic $100 budget as real | Under-resourcing | High if believed | Explicitly disregard |
| R-12 | Patient login via medical ID weak auth | Account takeover | Medium | Strong secret + rate limit + recovery |
| R-13 | Hardcoding role names for approval | Fragile authz | Low if followed | Always check `results.approve` permission |

---

## 5. Unclear Requirements Needing User Input

Remaining (not locked):

1. WhatsApp provider  
2. PDF brand template  
3. Hosting + data residency  
4. Retention policy  
5. Whether patients see critical flags  
6. Multi-lab in v1?  
7. CAT-04 medical sign-off of catalog ranges before go-live  

**Resolved:** Approver (Admin via permission), Laravel, Analyzer Future, Receptionist out.

---

## 6. Risk Register Hygiene

Update this file when:

- User answers a decision gate  
- New source document version arrives  
- A production incident or failed AC reveals a new risk  
