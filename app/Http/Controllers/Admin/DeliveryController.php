<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Models\NotificationDelivery;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        $deliveries = NotificationDelivery::query()
            ->with(['notification.user', 'notification.result'])
            ->latest()
            ->paginate(30);

        $reports = GeneratedReport::query()
            ->with(['patient', 'result.test', 'order'])
            ->latest('generated_at')
            ->paginate(20, ['*'], 'reports_page');

        return view('admin.deliveries.index', compact('deliveries', 'reports'));
    }
}
