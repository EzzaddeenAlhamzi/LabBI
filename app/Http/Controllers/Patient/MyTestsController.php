<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\TestOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTestsController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        $orders = TestOrder::with(['items.test', 'items.result', 'doctor.user'])
            ->where('patient_id', $patient->id)
            ->latest('ordered_at')
            ->paginate(20);

        return view('patient.tests.index', compact('orders', 'patient'));
    }

    public function show(Request $request, Result $result): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);
        abort_unless($result->patient_id === $patient->id, 403);

        // Historical visibility: approved results + order status for in-progress
        if ($result->status !== Result::STATUS_APPROVED) {
            // Allow viewing status of own non-approved results without full clinical detail leakage of drafts
            $result->load(['test', 'order']);

            return view('patient.results.status', compact('result', 'patient'));
        }

        $result->load(['test', 'order.doctor.user', 'attachments']);

        return view('patient.results.show', compact('result', 'patient'));
    }
}
