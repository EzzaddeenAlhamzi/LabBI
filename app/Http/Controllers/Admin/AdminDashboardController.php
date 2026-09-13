<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Bi\BIQueryService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(BIQueryService $bi): View
    {
        $dashboard = $bi->dashboard();

        return view('admin.dashboard', [
            'stats' => [
                'users' => null, // kept for backward-compatible blade keys if needed
            ],
            'cards' => $dashboard['cards'],
            'performance' => $dashboard['performance'],
        ]);
    }
}
