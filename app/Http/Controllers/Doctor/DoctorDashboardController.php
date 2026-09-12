<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\TestOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        return view('doctor.dashboard', [
            'ordersCount' => TestOrder::where('doctor_id', $doctor->id)->count(),
            'patientsCount' => Patient::where('primary_doctor_id', $doctor->id)->count(),
            'recentOrders' => TestOrder::with('patient')
                ->where('doctor_id', $doctor->id)
                ->latest('ordered_at')
                ->limit(5)
                ->get(),
        ]);
    }
}