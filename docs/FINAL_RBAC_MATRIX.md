# LabBI — Final RBAC Matrix (Phase 5)

**Rule:** Prefer permission codes over role-name hardcoding (REV-05 / D-008).  
**Tests:** `Phase1AcceptanceTest` AC-21 + `Phase5AcceptanceTest::test_final_rbac_direct_url_denials`

---

## Roles (V1)

| Role code | Arabic | Notes |
|---|---|---|
| `admin` | الإدارة | All permissions including `results.approve` |
| `lab_technician` | فني المختبر | Lab ops; **no** approve; **no** Admin BI / audit / backups |
| `doctor` | الطبيب | Own patients/orders/results; comparison |
| `patient` | المريض | Self only |

No Receptionist (D-009).

---

## Phase 5 surfaces

| Surface | Permission | Admin | Lab | Doctor | Patient |
|---|---|---|---|---|---|
| Audit log UI `/admin/audit-logs` | `audit.view` | ✓ | 403 | 403 | 403 |
| Backup UI `/admin/backups` | `backups.manage` | ✓ | 403 | 403 | 403 |
| Restore POST | `backups.manage` + confirm | ✓ | 403 | 403 | 403 |
| Settings `/admin/settings` | `settings.manage` | ✓ | 403 | 403 | 403 |
| Admin BI `/admin/bi` | `bi.view` | ✓ | 403 | 403 | 403 |
| Health `/health` | public | ✓ | ✓ | ✓ | ✓ |

---

## Clinical portals (summary)

| Area | Admin | Lab | Doctor | Patient |
|---|---|---|---|---|
| Users / roles / permissions | ✓ | 403 | 403 | 403 |
| Patients all | ✓ | ✓ (lab list) | assigned | self |
| Orders create | ✓ | — | ✓ | 403 |
| Sample receive / result entry | ✓ | ✓ | 403 | 403 |
| Result approve | ✓ (`results.approve`) | 403 | 403 | 403 |
| Critical lab board | ✓ | ✓ | own | 403 |
| Doctor comparison | — | 403 | own patients | 403 |
| Patient results | — | — | — | approved self |
| PDF download | clinical/own per ACL | clinical | clinical | own |

Full permission seed list: `database/seeders/DatabaseSeeder.php` + `docs/ROLES_AND_PERMISSIONS.md`.

---

## Direct URL denial (A9)

For each non-admin role, these must return **403**:

- `/admin/audit-logs`
- `/admin/backups`
- `/admin/settings`
- `/admin/bi`
- `/admin/users` (doctor/patient)

Patient must not open `/lab/*` entry routes.
