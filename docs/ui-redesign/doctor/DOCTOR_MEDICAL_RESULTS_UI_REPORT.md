# Doctor Medical Results UI Report

- results/index: approved results only (controller enforces STATUS_APPROVED); value+unit LTR, classification badges, status badges.
- results/show: prominent value display, classification badge, range snapshot, critical banner, critical alert timestamps, attachments list, PDF download when report exists.
- critical/index: alert list with thresholds, status badges, link to result.
- comparison/index: filter form + trend badge + approved rows table with classification badges; limitations list preserved.
- No clinical flags computed client-side; all values server-provided.
