<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);
        abort_unless($request->user()->hasPermission('pdf.download_clinical'), 403);

        $reports = GeneratedReport::query()
            ->with(['patient', 'result.test', 'order'])
            ->whereHas('order', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->latest('generated_at')
            ->paginate(20);

        return view('doctor.reports.index', compact('reports'));
    }
}
