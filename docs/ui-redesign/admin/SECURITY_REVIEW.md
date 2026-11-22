# Security Review

| Area | Result |
|------|--------|
| Authentication | Unchanged — same `auth`/`active` middleware, login flow, throttling |
| Authorization | Unchanged — all `permission:*` middleware intact; no permission checks added/removed in views (buttons were never permission-gated client-side originally; server enforcement identical) |
| CSRF | All forms retain `@csrf`; `@method('PUT')` spoofing preserved |
| XSS | All output still escaped via `{{ }}`; JSON chart data serialized with `@json`; no `{!! !!}` added except static SVG icon paths in the icon component (developer-controlled constants, no user input) |
| Secrets | No secrets in views/CSS/JS; settings page still shows only non-secret config keys; `.env` untouched and uncommitted |
| Sensitive data | Audit log view still shows sanitized metadata only; patient data scope unchanged |
| Destructive ops | Backup restore still requires acknowledge checkbox + typed `RESTORE` + server validation |
| Dependencies | Zero new dependencies — no supply-chain surface added |
| Session/roles | Role isolation preserved: admin layout only used by admin views; doctor/lab/patient layouts untouched; shared pages use role-conditional markup |

## Notes

- The new `BIQueryService` methods are read-only `selectRaw` aggregates on existing tables — no injection vectors (no user input interpolated; filters bound via query builder)
- Icon component uses `{!! $d !!}` for path constants defined in the same file — safe, not user-controllable
