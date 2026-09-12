<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Services\AuditLogger;
use Illuminate\View\View;

class LabDashboardController extends Controller
{
    public function __invoke(): View
    {
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
        ]);
    }
}
