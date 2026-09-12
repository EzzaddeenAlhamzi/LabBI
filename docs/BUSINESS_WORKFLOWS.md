# LabBI — Business Workflows

**Status:** Phase 0 planning (not implemented)

---

## 1. Master Lifecycle

```
تسجيل المريض → إنشاء طلب الفحص → استقبال العينة → تنفيذ التحليل
→ إدخال/استقبال النتائج → التدقيق البرمجي → اكتشاف القيم الحرجة
→ مراجعة/اعتماد → إشعار الطبيب → إشعار المريض → إصدار PDF
→ الأرشفة → BI والتقارير
```

English:

Patient registration → Create order → Receive sample → Perform analysis → Enter results → Programmatic audit → Critical detection → Review/approve → Notify doctor → Notify patient → PDF → Archive → BI

---

## 2. Workflow A — Patient Registration

**Actors:** Admin, Lab (or Receptionist if added)  
**Requirements:** PAT-01, PAT-02, AC-03

1. Enter demographics (name, phone, etc.)  
2. System assigns **unique Medical File Number**  
3. Optionally link primary doctor and create portal user  
4. Soft-delete only for removal paths  
5. Audit: patient created/updated  

---

## 3. Workflow B — Create & Submit Test Order

**Actors:** Doctor (primary), Admin  
**Requirements:** ORD-01…ORD-07, ORD-11, AC-04, AC-05

1. Select patient + one or more catalog tests  
2. System records doctor, timestamp, unique order number  
3. Status = `requested`  
4. Order appears on Laboratory “New Orders” queue  
5. Audit: order created/submitted  

**Note:** Source lab text says lab receives orders from doctors; doctor create-order is mandatory despite omission in one doctor feature list (GAP-01).

---

## 4. Workflow C — Sample Reception

**Actors:** Lab Technician  
**Requirements:** SMP-01…SMP-08, AC-06

1. Open order from queue  
2. Confirm physical sample arrival  
3. Match sample to order/patient/test  
4. Record receiver + date/time; status → `sample_received`  
5. If unsuitable → `rejected` with reason; notify doctor as needed  
6. Else start analysis → `in_analysis`  

---

## 5. Workflow D — Result Entry & Programmatic Audit

**Actors:** Lab Technician  
**Requirements:** RES-01…RES-09, RES-11, AC-07

1. Open in-analysis order items  
2. Enter value(s), unit, notes; save draft allowed  
3. Upload attachments (PDF/images) as needed  
4. On submit: server validation  
5. Invoke **Critical Value Engine**  
6. Status → `pending_review`  

**Analyzer path:** Out of default scope; if enabled later, ingestion must land in same submit/engine pipeline.

---

## 6. Workflow E — Critical Value Engine

**Actors:** System  
**Requirements:** CRT-01…CRT-12, AC-08…AC-12

1. Load normal + critical ranges for test (snapshot)  
2. Compare value  
3. Classify `normal` | `abnormal` | `critical`  
4. Set color indicator metadata  
5. If critical:  
   - Create `critical_alerts` row with `detected_at`  
   - Create notification for doctor (immediate)  
   - Record `notified_at` when delivery attempted  
   - Surface on critical dashboards  
6. If normal: **no** critical alert (AC-08)  

---

## 7. Workflow F — Review & Approval

**Actors:** Approver role TBD (REV-05)  
**Requirements:** REV-01…REV-04, RES-10, AC-13

1. Reviewer opens Pending Review queue  
2. Sees values, classification, attachments, notes  
3. **Approve** → status `ready`/`completed` path; record approver + time  
4. **Return** → lab corrects; re-enter engine on resubmit  
5. Trigger communication & PDF jobs on approval  

---

## 8. Workflow G — Notify Doctor & Patient

**Actors:** System (+ Email/WhatsApp providers)  
**Requirements:** NTF-*, EML-*, WA-*, AC-11, AC-15–AC-17

### Doctor
- In-app notification of result  
- Email with patient basics, test name, status, PDF or secure link  
- Immediate alert channel for critical cases  

### Patient
- In-app readiness notification  
- WhatsApp readiness (+ optional secure link)  
- Delivery statuses logged (`sent`/`failed`)  

---

## 9. Workflow H — PDF & Archive

**Actors:** System, Lab, Doctor, Patient (download)  
**Requirements:** PDF-*, ARC-*, AC-14, AC-18

1. Generate PDF from approved template (template TBD)  
2. Store in `generated_reports` / attachments  
3. Mark order/result archived on patient file  
4. Patient file tabs show orders, results, history, attachments, critical, reports  

---

## 10. Workflow I — Historical Comparison

**Actors:** Doctor  
**Requirements:** CMP-*, PAT-10, AC-19

1. Select patient, test, date range  
2. List chronological results with classification  
3. Compute trend: rising / falling / stable + color  

---

## 11. Workflow J — Admin BI Monitoring

**Actors:** Admin  
**Requirements:** BI-*, AC-20

1. Dashboard cards (counts by status, critical, today)  
2. Performance: volume, avg TAT, critical handling time  
3. Filtered operational reports (date, test, department, status)  

---

## 12. Workflow K — Patient Review / Re-test Request

**Actors:** Patient → Doctor  
**Requirements:** PTP-07, GAP-08

1. Patient submits structured request (interpretation or re-test)  
2. Doctor receives request list (not necessarily live chat)  
3. Doctor responds / creates follow-up order as needed  

---

## 13. Workflow L — Backup & Restore

**Actors:** Admin / System scheduler  
**Requirements:** BKP-*, AC-22, AC-23

1. Daily automatic backup job  
2. Record metadata (date, status, size, location)  
3. Admin-triggered restore in emergency (controlled procedure)  

---

## 14. Exception Paths

| Exception | Handling |
|---|---|
| Sample mismatch / poor quality | Reject sample; order deferred/rejected; notify doctor |
| Critical value | Alert + priority notification; still requires review/approval policy |
| Notification provider failure | Mark delivery failed; retry; keep in-app notification |
| Unauthorized access attempt | Deny + security/audit log |
| Inactive user | Block authentication |

---

## 15. TAT Definition (planning default)

Until clinically finalized:

- **Order TAT:** `ordered_at` → `approved_at` / `completed_at`  
- **Critical response TAT:** `detected_at` → `notified_at` (and optionally `acknowledged_at`)  

Confirm with stakeholders before BI go-live.
