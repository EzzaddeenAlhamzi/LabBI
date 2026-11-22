# Patient Security Review

- results.show aborts unless result->patient_id === own id; records.show aborts cross-patient.
- Non-approved results render status page only — no values.
- Dashboard charts scoped by patientId — own orders only.
- No auth/route/middleware/validation changes; CSRF/escaping/audit intact.
