<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'patients' => Patient::count(),
                'orders' => TestOrder::count(),
                'samples' => Sample::count(),
            ],
        ]);
    }
}