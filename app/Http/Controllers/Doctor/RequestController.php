<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\PatientRequest;
use App\Services\PatientRequestService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);
        abort_unless($request->user()->hasPermission('requests.manage_doctor'), 403);

        $requests = PatientRequest::query()
            ->with(['patient', 'result.test'])
            ->where('doctor_id', $doctor->id)
            ->latest()
            ->paginate(20);

        return view('doctor.requests.index', compact('requests'));
    }

    public function underReview(Request $request, PatientRequest $patientRequest, PatientRequestService $service): RedirectResponse
    {
        try {
            $service->setUnderReview($patientRequest, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['request' => $e->getMessage()]);
        }

        return back()->with('success', 'الطلب قيد المراجعة.');
    }

    public function resolve(Request $request, PatientRequest $patientRequest, PatientRequestService $service): RedirectResponse
    {
        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:2000']]);
        try {
            $service->resolve($patientRequest, $request->user(), $data['resolution_notes']);
        } catch (DomainException $e) {
            return back()->withErrors(['request' => $e->getMessage()]);
        }

        return back()->with('success', 'تم حل الطلب.');
    }

    public function reject(Request $request, PatientRequest $patientRequest, PatientRequestService $service): RedirectResponse
    {
        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:2000']]);
        try {
            $service->reject($patientRequest, $request->user(), $data['resolution_notes']);
        } catch (DomainException $e) {
            return back()->withErrors(['request' => $e->getMessage()]);
        }

        return back()->with('success', 'تم رفض الطلب.');
    }
}
