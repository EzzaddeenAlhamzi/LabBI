<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\TestOrder;
use App\Services\Bi\OperationalReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalReportController extends Controller
{
    /** @return list<string> */
    public static function allowedStatuses(): array
    {
        return OperationalReportService::allowedOrderStatuses();
    }

    public function index(Request $request, OperationalReportService $reports): View
    {
        $filters = BiReportFilters::fromArray(
            $request->only(['date_from', 'date_to', 'test_id', 'doctor_id', 'department_id', 'status', 'patient_id']),
            self::allowedStatuses(),
        );

        $report = $reports->ordersReport($filters);
        $volume = $reports->statusVolume($filters);

        return view('admin.bi.operational', [
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'limitations' => $report['limitations'],
            'filters' => $filters,
            'volume' => $volume,
            'tests' => LabTest::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'doctors' => Doctor::query()->with('user')->orderBy('id')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'statuses' => collect(self::allowedStatuses())
                ->mapWithKeys(fn ($s) => [$s => TestOrder::statusLabel($s)]),
        ]);
    }
}
