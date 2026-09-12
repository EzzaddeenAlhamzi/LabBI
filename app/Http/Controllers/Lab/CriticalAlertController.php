<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\CriticalAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriticalAlertController extends Controller
{
    public function index(): View
    {
        $alerts = CriticalAlert::with(['patient', 'test', 'order', 'result', 'doctor.user'])
            ->latest('discovered_at')
            ->paginate(25);

        return view('lab.critical.index', compact('alerts'));
    }

    public function show(CriticalAlert $alert): View
    {
        $alert->load(['patient', 'test', 'order.doctor.user', 'result.enteredBy', 'doctor.user']);

        return view('lab.critical.show', compact('alert'));
    }

    public function acknowledge(Request $request, CriticalAlert $alert): RedirectResponse
    {
        if ($alert->status === CriticalAlert::STATUS_OPEN) {
            $alert->update([
                'status' => CriticalAlert::STATUS_ACKNOWLEDGED,
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'تم تسجيل الاطلاع على التنبيه الحرج.');
    }
}
