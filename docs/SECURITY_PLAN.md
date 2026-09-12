# LabBI — Security Plan

**Status:** Phase 0 planning (not implemented)  
Maps source security requirements + recommended controls from analysis §40.

---

## 1. Security Objectives

Protect confidentiality, integrity, and availability of laboratory and patient data while enforcing RBAC so **no user reaches unauthorized information** (AUTH-04, AC-21).

---

## 2. Controls Traceability

| Control | Source / ID | Plan |
|---|---|---|
| HTTPS/SSL | SEC-01, NFR-SEC-01 | TLS termination at reverse proxy; HSTS in production |
| Password hashing | AUTH-06, SEC-02 | bcrypt or Argon2id; **do not** store raw SHA-256 password hashes alone |
| AES-256 at rest | SEC-03, NFR-SEC-02 | Encrypt selected sensitive columns; disk encryption on hosts |
| SQL Injection defense | SEC-04 | ORM / prepared statements only |
| XSS defense | SEC-05 | Context escaping, CSP baseline, sanitize uploads metadata |
| RBAC | SEC-06, AUTH-03 | Policies on every route/query |
| CSRF | SEC-REC-01 | Tokens for cookie-session mutations |
| Server validation | SEC-REC-02 | Form requests / DTO validation |
| Rate limiting | SEC-REC-03 | Login and sensitive endpoints |
| Secure cookies | SEC-REC-04 | `Secure`, `HttpOnly`, `SameSite` |
| Session expiration | SEC-REC-05, AUTH-05 | Idle + absolute timeouts |
| Hide internal errors | SEC-REC-07 | Generic error pages; detailed logs server-side |
| Security event logging | SEC-REC-08 | Failed logins, denied access, permission changes |

---

## 3. Data Classification

| Class | Examples | Handling |
|---|---|---|
| Public | Login page chrome | No auth |
| Internal ops | BI aggregates | Admin (+ limited lab) |
| Confidential PHI | Patient demographics, results, attachments, PDFs | Encrypt in transit; ACL; audit views/downloads |
| Secrets | Provider API keys, APP_KEY | Env vault; never commit |

---

## 4. Authentication Design

- Unique credentials per user; optional patient login via medical file number + secret  
- Lockout / throttling after repeated failures  
- Password recovery (GAP-11) recommended before production  
- Deactivated accounts cannot authenticate  

---

## 5. Authorization Design

- Role permissions from `ROLES_AND_PERMISSIONS.md`  
- Row-level scoping for doctor/patient  
- Deny by default  
- Admin actions on roles/permissions always audited  

---

## 6. Application Security Practices

1. Dependency vulnerability scanning in CI  
2. Least-privilege DB user for app  
3. Separate credentials per environment  
4. File uploads: extension allowlist (PDF/images), size caps, store outside public root  
5. Signed temporary URLs for PDF/WhatsApp links  
6. Critical/result APIs never accept client-supplied `classification` as authoritative  

---

## 7. Encryption Notes

- **Transit:** TLS 1.2+  
- **Passwords:** adaptive hash (bcrypt/Argon2)  
- **Fields:** AES-256-GCM (or framework encrypted casts) for agreed sensitive columns (national ID, etc.)  
- **Backups:** encrypted at rest where storage allows  

Exact field list is an open decision.

---

## 8. Audit & Monitoring

Mandatory audit events (AUD-02): patient create/edit, order create, sample receive, result enter/edit/approve, critical detection, alert send, report view, permission changes.

Security monitoring: failed auth spikes, repeated 403s, backup failures.

---

## 9. Backup Security

- Daily automated backups (BKP-01)  
- Access to restore limited to admin  
- Test restores on staging (AC-23)  
- Retention period TBD (GAP-10)  

---

## 10. Compliance Posture (planning)

Source is an academic/analysis document, not a full regulatory package. Before production clinical use, stakeholders should confirm applicable local health-data rules (retention, consent, hosting residency). Tracked as risk in `RISKS_AND_ASSUMPTIONS.md`.

---

## 11. Phase Mapping

| Phase | Security work |
|---|---|
| P1 | Auth, hashing, RBAC, CSRF, validation, SQLi/XSS baselines |
| P2 | Result/critical integrity, audit events |
| P3 | Secure links, provider secrets, delivery logs |
| P4 | BI access control |
| P5 | AES field encryption, HTTPS hardening, rate limits, backup encryption, security review |
