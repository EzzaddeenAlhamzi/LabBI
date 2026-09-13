<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;

/**
 * Explicit doctor↔patient authorization (no open claim of unassigned patients).
 * Authorized when: primary_doctor_id matches, or doctor already has an order for the patient.
 * Admin assignment of primary_doctor_id is the only way to establish a new relationship.
 */
class DoctorPatientAccess
{
    public function canAccess(Doctor $doctor, Patient $patient): bool
    {
        if ($patient->primary_doctor_id === $doctor->id) {
            return true;
        }

        return $patient->orders()->where('doctor_id', $doctor->id)->exists();
    }

    public function assertCanAccess(Doctor $doctor, Patient $patient): void
    {
        abort_unless(
            $this->canAccess($doctor, $patient),
            403,
            'لا يمكنك الوصول إلى بيانات هذا المريض دون علاقة مصرّح بها.'
        );
    }
}
