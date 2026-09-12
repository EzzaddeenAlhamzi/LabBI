<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        $patient->load(['orders.items.test', 'primaryDoctor.user']);

        return view('patient.dashboard', compact('patient'));
    }

    public function profile(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        return view('patient.profile', compact('patient'));
    }

    public function show(Request $request, Patient $patient): View|\Illuminate\Http\RedirectResponse
    {
        $own = $request->user()->patient;
        abort_unless($own, 403);

        if ($patient->id !== $own->id) {
            abort(403, 'لا يمكنك الوصول إلى بيانات مريض آخر');
        }

        return view('patient.profile', ['patient' => $own]);
    }
}