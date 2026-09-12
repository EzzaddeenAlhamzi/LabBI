<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Workflow\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\TestOrder;
use Illuminate\View\View;

class CompletedTestsController extends Controller
{
    public function __invoke(): View
    {
        $orders = TestOrder::with(['patient', 'items.test', 'items.result'])
            ->whereIn('status', [OrderStatus::READY, OrderStatus::COMPLETED, OrderStatus::ARCHIVED])
            ->latest('updated_at')
            ->paginate(20);

        $results = Result::with(['patient', 'test', 'order'])
            ->where('status', Result::STATUS_APPROVED)
            ->latest('approved_at')
            ->limit(30)
            ->get();

        return view('lab.completed.index', compact('orders', 'results'));
    }
}
