<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $patients = Patient::query()
            ->where('primary_doctor_id', $doctor->id)
            ->orWhereHas('orders', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->with('primaryDoctor.user')
            ->distinct()
            ->paginate(15);

        return view('doctor.patients.index', compact('patients'));
    }

    public function show(Request $request, Patient $patient): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $allowed = $patient->primary_doctor_id === $doctor->id
            || $patient->orders()->where('doctor_id', $doctor->id)->exists();
        abort_unless($allowed, 403, 'لا يمكنك الوصول إلى بيانات هذا المريض');

        $patient->load(['orders' => fn ($q) => $q->where('doctor_id', $doctor->id)->with('items.test')]);

        return view('doctor.patients.show', compact('patient'));
    }
}