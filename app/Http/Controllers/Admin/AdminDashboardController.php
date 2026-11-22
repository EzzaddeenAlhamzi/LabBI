<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Bi\BiReportFilters;
use App\Http\Controllers\Controller;
use App\Services\Bi\BIQueryService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(BIQueryService $bi): View
    {
        $dashboard = $bi->dashboard();
        $filters = new BiReportFilters;

        return view('admin.dashboard', [
            'stats' => [
                'users' => null, // kept for backward-compatible blade keys if needed
            ],
            'cards' => $dashboard['cards'],
            'performance' => $dashboard['performance'],
            'orderSeries' => $bi->ordersDailySeries($filters),
            'statusBreakdown' => $bi->statusBreakdown($filters),
            'departmentWorkload' => $bi->departmentWorkload(),
        ]);
    }
}
