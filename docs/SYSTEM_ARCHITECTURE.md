# LabBI — System Architecture

**Status:** Phase 0 planning (not implemented)  
**Constraints from source:** Web-Based · PHP · MySQL · APIs · Arabic UI · RBAC · HTTPS · AES-256 · modular & scalable

---

## 1. Architectural Style

**Modular monolith** (recommended for Phase 1–5):

- Single deployable PHP application with clear module boundaries  
- Shared MySQL database  
- HTTP JSON API + server-rendered or SPA web portals  
- Background workers/queues for email, WhatsApp, PDF, backups  

**Rationale:** Matches PHP/MySQL mandate, graduation/project delivery speed, and maintainability (NFR-MAINT-01) without premature microservices complexity. Module boundaries enable future extraction (mobile, analyzer adapters).

See `DECISIONS.md` for Laravel vs plain PHP.

---

## 2. Logical Layers

```
┌─────────────────────────────────────────────────────────────┐
│  Presentation: Admin / Lab / Doctor / Patient Web Portals   │
│  (HTML/CSS/JS, Arabic RTL)                                   │
├─────────────────────────────────────────────────────────────┤
│  API Gateway surface (same app): REST/JSON + session/token   │
├─────────────────────────────────────────────────────────────┤
│  Application Services (use-cases per module)                 │
│  Auth · Users · Patients · Catalog · Orders · Samples ·      │
│  Results · Critical Engine · Review · Notifications · PDF ·  │
│  BI · Audit · Backup                                         │
├─────────────────────────────────────────────────────────────┤
│  Domain / Workflow engine (order & sample state machines)    │
├─────────────────────────────────────────────────────────────┤
│  Infrastructure                                              │
│  MySQL · File/Object storage · Mail · WhatsApp adapter ·     │
│  Queue · Scheduler · Encryption · Logging                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Portal Surfaces

| Portal | Primary actors | Key capabilities |
|---|---|---|
| Admin | إدارة المختبر | Users, RBAC, catalog, BI, audit, backup, settings |
| Laboratory | فني المختبر | Orders queue, samples, results, review, critical alerts |
| Doctor | طبيب | Patients, create orders, results, critical, comparison, PDF |
| Patient | مريض | Own profile, results, history, PDF download, notifications, review requests |
| Shared | All authenticated | Login, profile, notification center, unauthorized page |

---

## 4. Core Runtime Components

| Component | Responsibility |
|---|---|
| Auth & Session | Login, password hashing, sessions/tokens, RBAC enforcement |
| Critical Value Engine | Pure domain service: classify Normal/Abnormal/Critical; emit alerts |
| Workflow Service | Enforce order/sample/result status transitions |
| Notification Orchestrator | Fan-out to in-app, email, WhatsApp with delivery logs |
| PDF Generator | Render approved result reports; store artifacts |
| Audit Writer | Append-only operational/security events |
| Backup Job | Daily DB (+ files) backup metadata & restore tools |
| BI Query Service | Aggregations for cards, TAT, operational reports |

---

## 5. Data Stores

| Store | Use |
|---|---|
| MySQL | Transactional system of record (see `DATABASE_DESIGN.md`) |
| Local disk or S3-compatible object storage | Attachments, generated PDFs, backup blobs |
| Cache (optional Redis) | Sessions, rate limits, dashboard caches — **open** if introduced |

Sensitive fields (e.g., national ID, phone if required) may use application-level AES-256 encryption in addition to disk/volume encryption — details in `SECURITY_PLAN.md`.

---

## 6. Integration Architecture

```
LabBI App
  ├── SMTP / transactional email provider (doctor PDFs & alerts)
  ├── WhatsApp Business API adapter (provider TBD)
  ├── (Future) Laboratory Analyzer Adapter (HL7/ASTM/vendor API)
  └── (Future) Mobile clients consuming same REST API
```

**Adapters must be interface-driven** so WhatsApp provider and analyzer protocols can change without rewriting core workflow.

---

## 7. Deployment Topology (portable)

Source allows **Local Server** or **Cloud Hosting**; architecture must not lock to one vendor.

### Target shape

```
[ Browser ] --HTTPS--> [ Reverse proxy / TLS termination ]
                              |
                         [ PHP-FPM / Web ]
                              |
              +---------------+---------------+
              |               |               |
           [ MySQL ]    [ Queue worker ]  [ Object storage ]
                              |
                        [ Cron / Scheduler ]
                        (backup, retries)
```

Environments: `local` · `staging` · `production`.

---

## 8. Cross-Cutting Concerns

| Concern | Approach |
|---|---|
| i18n | Arabic-first UI strings; optional future bilingual PDF |
| Authorization | Policy/permission checks on every query scoped to actor |
| Observability | App logs + audit_logs + notification_deliveries |
| Idempotency | Notification/PDF jobs keyed by result/approval events |
| Multi-lab readiness | `departments` (+ optional `laboratories` later) without rewriting core |

---

## 9. Security Architecture (summary)

- TLS everywhere outside trusted network  
- Hashed passwords (bcrypt/Argon2 recommended)  
- RBAC at route/service/query level  
- ORM/parameterized SQL  
- XSS escaping + CSP baseline  
- CSRF on cookie sessions  
- Encrypted sensitive columns where mandated  
- Rate-limited authentication  

Full plan: `SECURITY_PLAN.md`.

---

## 10. What Is Explicitly Not in Architecture (now)

- Microservices mesh  
- Real-time diagnostic AI  
- Native mobile apps  
- Inventory/ERP/accounting subsystems  
- Hard dependency on a specific cloud or WhatsApp vendor  

---

## 11. Open Architecture Questions

1. Laravel vs other PHP frameworks vs structured plain PHP  
2. Server-rendered Blade vs SPA (Vue/React) for portals  
3. Redis necessity in MVP  
4. Single-lab vs multi-lab tenancy model for v1  
5. Analyzer integration in scope or Future only  

Decisions tracked in `DECISIONS.md`.
