<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Services\Bi\CriticalAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriticalCasesController extends Controller
{
    public function index(Request $request, CriticalAnalyticsService $analytics): View
    {
        $filters = BiReportFilters::fromArray(
            $request->only(['date_from', 'date_to', 'test_id', 'doctor_id', 'department_id', 'status', 'patient_id']),
            CriticalAnalyticsService::allowedStatuses(),
        );

        $dashboard = $analytics->dashboard($filters);

        return view('admin.bi.critical', [
            'summary' => $dashboard['summary'],
            'rows' => $dashboard['rows'],
            'limitations' => $dashboard['limitations'],
            'filters' => $filters,
            'tests' => LabTest::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'doctors' => Doctor::query()->with('user')->orderBy('id')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'statuses' => [
                CriticalAlert::STATUS_OPEN => 'مفتوح',
                CriticalAlert::STATUS_ACKNOWLEDGED => 'مُقرّ',
                CriticalAlert::STATUS_CLOSED => 'مغلق',
            ],
        ]);
    }
}
