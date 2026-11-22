<?php

namespace App\Http\Controllers\Doctor;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\Patient;
use App\Models\Result;
use App\Models\TestOrder;
use App\Services\Bi\BIQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorDashboardController extends Controller
{
    public function __invoke(Request $request, BIQueryService $bi): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $seriesTo = Carbon::now()->endOfDay();
        $seriesFilters = new BiReportFilters(
            dateFrom: Carbon::now()->subDays(13)->startOfDay(),
            dateTo: $seriesTo,
            doctorId: $doctor->id,
        );
        $workloadFilters = new BiReportFilters(
            dateFrom: Carbon::now()->subDays(29)->startOfDay(),
            dateTo: $seriesTo,
            doctorId: $doctor->id,
        );

        return view('doctor.dashboard', [
            'ordersCount' => TestOrder::where('doctor_id', $doctor->id)->count(),
            'patientsCount' => Patient::where('primary_doctor_id', $doctor->id)->count(),
            'resultsCount' => Result::whereHas('order', fn ($q) => $q->where('doctor_id', $doctor->id))
                ->where('status', Result::STATUS_APPROVED)->count(),
            'criticalCount' => CriticalAlert::where('doctor_id', $doctor->id)
                ->where('status', CriticalAlert::STATUS_OPEN)->count(),
            'recentOrders' => TestOrder::with('patient')
                ->where('doctor_id', $doctor->id)
                ->latest('ordered_at')
                ->limit(5)
                ->get(),
            'ordersToday' => TestOrder::where('doctor_id', $doctor->id)
                ->whereDate('ordered_at', Carbon::today())->count(),
            'orderSeries' => $bi->ordersDailySeries($seriesFilters, 14),
            'statusBreakdown' => $bi->statusBreakdown($workloadFilters),
        ]);
    }
}
