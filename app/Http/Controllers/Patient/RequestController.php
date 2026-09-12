<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Models\PatientRequest;
use App\Models\Result;
use App\Services\PatientRequestService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);
        abort_unless($request->user()->hasPermission('requests.view_own'), 403);

        $requests = PatientRequest::query()
            ->with(['doctor.user', 'result.test'])
            ->where('patient_id', $patient->id)
            ->latest()
            ->paginate(20);

        $approvedResults = Result::query()
            ->with('test')
            ->where('patient_id', $patient->id)
            ->where('status', Result::STATUS_APPROVED)
            ->latest('approved_at')
            ->get();

        return view('patient.requests.index', compact('requests', 'approvedResults', 'patient'));
    }

    public function store(Request $request, PatientRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:interpretation,retest'],
            'message' => ['required', 'string', 'max:2000'],
            'result_id' => ['nullable', 'integer', 'exists:results,id'],
        ]);

        try {
            $service->submit($request->user(), $data);
        } catch (DomainException $e) {
            return back()->withErrors(['request' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'تم إرسال الطلب.');
    }
}
