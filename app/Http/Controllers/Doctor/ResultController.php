<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\GeneratedReport;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $results = Result::with(['patient', 'test', 'order'])
            ->whereHas('order', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->where('status', Result::STATUS_APPROVED)
            ->latest('approved_at')
            ->paginate(20);

        return view('doctor.results.index', compact('results'));
    }

    public function show(Request $request, Result $result): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);
        abort_unless($result->order && $result->order->doctor_id === $doctor->id, 403);

        if ($result->status !== Result::STATUS_APPROVED) {
            abort(403);
        }

        $result->load(['patient', 'test', 'order', 'criticalAlert', 'attachments', 'approvedBy']);
        $report = GeneratedReport::query()
            ->where('result_id', $result->id)
            ->latest('id')
            ->first();

        return view('doctor.results.show', compact('result', 'report'));
    }

    public function critical(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $alerts = CriticalAlert::with(['patient', 'test', 'order', 'result'])
            ->where('doctor_id', $doctor->id)
            ->latest('discovered_at')
            ->paginate(20);

        return view('doctor.critical.index', compact('alerts'));
    }
}
