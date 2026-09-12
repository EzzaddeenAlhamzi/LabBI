<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use App\Models\TestOrder;
use Illuminate\View\View;

class LabDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('lab.dashboard', [
            'newOrders' => TestOrder::where('status', 'requested')->count(),
            'awaitingSamples' => Sample::where('status', 'awaiting')->count(),
            'recentOrders' => TestOrder::with('patient')->whereIn('status', ['requested', 'sample_received'])
                ->latest('ordered_at')->limit(8)->get(),
        ]);
    }
}