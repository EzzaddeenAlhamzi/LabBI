# Doctor Patient Interface Report

- patients/index: medical file number, name, phone, view action — original columns preserved.
- patients/show: patient summary card (name, file number, phone, order count) + doctor's orders for that patient with test names, status badges, links.
- Access control unchanged: DoctorPatientAccess::assertCanAccess; orders eager-loaded scoped to doctor_id.
