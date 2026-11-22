<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Workflow\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Services\Bi\BIQueryService;
use Carbon\Carbon;
use Illuminate\View\View;

class LabDashboardController extends Controller
{
    public function __invoke(BIQueryService $bi): View
    {
        $now = Carbon::now();
        $seriesFrom = $now->copy()->subDays(13)->startOfDay();
        $seriesTo = $now->copy()->endOfDay();
        $workloadFrom = $now->copy()->subDays(29)->startOfDay();

        $seriesFilters = new BiReportFilters(
            dateFrom: $seriesFrom,
            dateTo: $seriesTo,
        );
        $workloadFilters = new BiReportFilters(
            dateFrom: $workloadFrom,
            dateTo: $seriesTo,
        );

        return view('lab.dashboard', [
            'newOrders' => TestOrder::where('status', OrderStatus::REQUESTED)->count(),
            'awaitingSamples' => Sample::whereIn('status', ['awaiting'])->count()
                + TestOrder::where('status', OrderStatus::REQUESTED)->count(),
            'inAnalysis' => TestOrder::where('status', OrderStatus::IN_ANALYSIS)->count(),
            'needsReview' => Result::where('status', Result::STATUS_PENDING_REVIEW)->count(),
            'criticalOpen' => CriticalAlert::where('status', CriticalAlert::STATUS_OPEN)->count(),
            'ready' => TestOrder::where('status', OrderStatus::READY)->count(),
            'deferred' => TestOrder::where('status', OrderStatus::DEFERRED)->count(),
            'rejected' => TestOrder::where('status', OrderStatus::REJECTED)->count(),
            'recentOrders' => TestOrder::with('patient')
                ->whereIn('status', [
                    OrderStatus::REQUESTED,
                    OrderStatus::SAMPLE_RECEIVED,
                    OrderStatus::IN_ANALYSIS,
                    OrderStatus::PENDING_REVIEW,
                ])
                ->latest('ordered_at')->limit(10)->get(),
            'orderSeries' => $bi->ordersDailySeries($seriesFilters, 14),
            'statusBreakdown' => $bi->statusBreakdown($workloadFilters),
        ]);
    }
}
