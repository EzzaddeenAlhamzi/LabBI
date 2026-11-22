<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\TestOrder;
use App\Services\Bi\BIQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiDashboardController extends Controller
{
    public function __invoke(Request $request, BIQueryService $bi): View
    {
        $filters = BiReportFilters::fromArray(
            $request->only(['date_from', 'date_to', 'test_id', 'doctor_id', 'department_id', 'status']),
            OperationalReportController::allowedStatuses(),
        );

        $dashboard = $bi->dashboard($filters);

        return view('admin.bi.dashboard', [
            'cards' => $dashboard['cards'],
            'performance' => $dashboard['performance'],
            'orderSeries' => $bi->ordersDailySeries($filters),
            'statusBreakdown' => $bi->statusBreakdown($filters),
            'departmentWorkload' => $bi->departmentWorkload(),
            'generatedAt' => $dashboard['generated_at'],
            'filters' => $filters,
            'tests' => LabTest::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'doctors' => Doctor::query()->with('user')->orderBy('id')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'statuses' => collect(OperationalReportController::allowedStatuses())
                ->mapWithKeys(fn ($s) => [$s => TestOrder::statusLabel($s)]),
        ]);
    }
}
