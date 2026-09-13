<?php

namespace App\Http\Controllers\Doctor;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\Patient;
use App\Services\Bi\HistoricalComparisonService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    public function index(Request $request, HistoricalComparisonService $comparison): View
    {
        $actor = $request->user();
        $patients = $comparison->accessiblePatients($actor);
        $tests = LabTest::query()->where('is_active', true)->orderBy('name_ar')->get();

        // Comparison is patient-scoped — do not force the BI reporting default window.
        // Explicit date pairs still respect MAX_PERIOD_DAYS (DoS bound).
        $filters = BiReportFilters::fromArray(
            $request->only(['date_from', 'date_to', 'test_id', 'patient_id']),
            [],
            applyDefaultPeriod: false,
        );

        $result = null;
        $error = null;

        if ($filters->patientId) {
            $patient = Patient::query()->find($filters->patientId);
            if (! $patient) {
                $error = 'المريض غير موجود.';
            } else {
                try {
                    $result = $comparison->compare($actor, $patient, $filters);
                } catch (DomainException $e) {
                    $error = $e->getMessage();
                }
            }
        }

        return view('doctor.comparison.index', [
            'patients' => $patients,
            'tests' => $tests,
            'filters' => $filters,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
