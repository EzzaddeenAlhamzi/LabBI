<?php

namespace App\Http\Controllers\Patient;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\Bi\BIQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientDashboardController extends Controller
{
    public function __invoke(Request $request, BIQueryService $bi): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        $patient->load(['orders.items.test', 'primaryDoctor.user']);

        $seriesTo = Carbon::now()->endOfDay();
        $seriesFilters = new BiReportFilters(
            dateFrom: Carbon::now()->subDays(13)->startOfDay(),
            dateTo: $seriesTo,
            patientId: $patient->id,
        );
        $workloadFilters = new BiReportFilters(
            dateFrom: Carbon::now()->subDays(29)->startOfDay(),
            dateTo: $seriesTo,
            patientId: $patient->id,
        );

        $approvedResults = Result::where('patient_id', $patient->id)
            ->where('status', Result::STATUS_APPROVED)->count();

        return view('patient.dashboard', [
            'patient' => $patient,
            'approvedResults' => $approvedResults,
            'orderSeries' => $bi->ordersDailySeries($seriesFilters, 14),
            'statusBreakdown' => $bi->statusBreakdown($workloadFilters),
        ]);
    }

    public function profile(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_unless($patient, 403);

        return view('patient.profile', compact('patient'));
    }

    public function show(Request $request, \App\Models\Patient $patient): View|\Illuminate\Http\RedirectResponse
    {
        $own = $request->user()->patient;
        abort_unless($own, 403);
        if ($own->id !== $patient->id) {
            abort(403, 'لا يمكنك الوصول إلى بيانات مريض آخر');
        }

        return view('patient.profile', ['patient' => $own]);
    }
}
