# LabBI — API Architecture

**Status:** Phase 3 Blade portals implement notifications/PDF/requests; REST `/api/v1` map remains the target contract for future mobile (not required for P3 exit).  
Source requires web UIs **and APIs** (نطاق المشروع), PHP backend, future mobile/analyzer readiness.

---

## 1. Style

| Choice | Recommendation | Notes |
|---|---|---|
| Protocol | HTTPS JSON REST | Simple, PHP-native |
| Versioning | `/api/v1/...` | Avoid breaking future mobile clients |
| Auth (web) | Session cookie + CSRF | Fits portal apps |
| Auth (API clients) | Token (Sanctum/Passport-like) optional | For future mobile/analyzer |
| Format | JSON request/response; ISO-8601 datetimes | Arabic messages in `message` fields OK |
| Errors | Problem-style `{ code, message, errors? }` | Never leak stack traces (SEC-REC-07) |

Final auth mode depends on Blade vs SPA decision (`DECISIONS.md`).

---

## 2. Cross-Cutting API Rules

1. Every endpoint enforces authentication (except login/password recovery if enabled).  
2. Every data read/write enforces RBAC **and** row-level scope (e.g., patient sees self only).  
3. Mutations that change clinical state go through Workflow service.  
4. Result submit triggers Critical Engine server-side (never trust client classification).  
5. Notification/PDF side effects are async jobs; APIs return accepted state + resource ids.  
6. Audit writer called on sensitive mutations.

---

## 3. Resource Map (v1)

### Auth
| Method | Path | Purpose | Roles |
|---|---|---|---|
| POST | `/api/v1/auth/login` | Login | Public |
| POST | `/api/v1/auth/logout` | Logout | Auth |
| GET | `/api/v1/auth/me` | Current user + roles | Auth |
| POST | `/api/v1/auth/password-recovery` | Optional GAP-11 | Public |

### Users / RBAC (Admin)
| Method | Path | Purpose |
|---|---|---|
| GET/POST | `/api/v1/users` | List/create |
| GET/PUT | `/api/v1/users/{id}` | View/update |
| PATCH | `/api/v1/users/{id}/status` | Activate/deactivate |
| GET/PUT | `/api/v1/roles` · `/permissions` · `/roles/{id}/permissions` | RBAC admin |

### Patients / Doctors / Staff
| Method | Path | Purpose |
|---|---|---|
| GET/POST | `/api/v1/patients` | Search/create |
| GET/PUT | `/api/v1/patients/{id}` | Details/update |
| GET | `/api/v1/patients/{id}/orders` · `/results` · `/attachments` · `/history` | Archive tabs |
| CRUD | `/api/v1/doctors`, `/api/v1/laboratory-staff` | Profiles |

### Catalog
| Method | Path | Purpose |
|---|---|---|
| CRUD | `/api/v1/tests` | Test catalog |
| CRUD | `/api/v1/tests/{id}/ranges` | Reference/critical ranges |

### Orders & Samples
| Method | Path | Purpose |
|---|---|---|
| POST | `/api/v1/orders` | Create (doctor/admin) |
| GET | `/api/v1/orders` | Search/filter by role scope |
| GET | `/api/v1/orders/{id}` | Details |
| POST | `/api/v1/orders/{id}/submit` | Send to lab |
| POST | `/api/v1/orders/{id}/samples/receive` | Confirm receipt |
| POST | `/api/v1/samples/{id}/reject` | Reject sample |
| POST | `/api/v1/samples/{id}/start-analysis` | Start analysis |

### Results & Review
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/orders/{id}/results` | List items/results |
| PUT | `/api/v1/results/{id}` | Draft/update values |
| POST | `/api/v1/results/{id}/submit` | Validate + run critical engine |
| POST | `/api/v1/results/{id}/approve` | Approve |
| POST | `/api/v1/results/{id}/return` | Return for correction |
| POST | `/api/v1/results/{id}/attachments` | Upload |

### Critical Alerts
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/critical-alerts` | List (lab/doctor/admin scoped) |
| POST | `/api/v1/critical-alerts/{id}/acknowledge` | Optional ack |

### Notifications / Comms
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/notifications` | Inbox |
| POST | `/api/v1/notifications/{id}/read` | Mark read |
| POST | `/api/v1/results/{id}/pdf` | Generate PDF |
| GET | `/api/v1/reports/{id}/download` | Secure download |

### Comparison & BI
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/patients/{id}/comparisons` | Query params: test_id, from, to |
| GET | `/api/v1/bi/summary` | Dashboard cards |
| GET | `/api/v1/bi/reports/*` | Operational reports |

### Patient requests
| Method | Path | Purpose |
|---|---|---|
| POST | `/api/v1/patient-requests` | Interpretation / re-test |
| GET | `/api/v1/patient-requests` | Patient/doctor lists |

### Admin ops
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/audit-logs` | Filterable |
| GET/POST | `/api/v1/backups` | List / trigger |
| POST | `/api/v1/backups/{id}/restore` | Restore (highly privileged) |
| GET/PUT | `/api/v1/settings` | System settings |

---

## 4. Example Event Flow (Critical Path)

```
PUT  /results/{id}            → save draft
POST /results/{id}/submit     → validate
                              → CriticalEngine.classify()
                              → if critical: create alert + enqueue doctor notify
POST /results/{id}/approve    → workflow → ready/completed
                              → enqueue PDF + doctor email + patient WhatsApp
                              → archive hooks
```

---

## 5. Pagination, Filtering, Idempotency

- List endpoints: `page`, `per_page`, `sort`, role-appropriate filters  
- Idempotency keys on notification/PDF job creation (`Idempotency-Key` header or unique job fingerprint)  
- File uploads: multipart; virus-scan hook optional later  

---

## 6. Future Analyzer Port

If ANL Option B approved, add:

`POST /api/v1/integrations/analyzers/{device}/results`  
- Machine credential / mTLS  
- Maps vendor payload → `results` draft/submit via same engine  

Do **not** implement until device questionnaire answered.

---

## 7. Open API Questions

1. Session vs token for first web MVP  
2. OpenAPI spec generation tooling  
3. Whether patient medical-file login uses same `/auth/login` with alternate identifier  
