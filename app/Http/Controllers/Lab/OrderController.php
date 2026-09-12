<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\TestOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = TestOrder::with(['patient', 'doctor.user', 'items.test']);

        if ($request->string('status')->toString() === 'new') {
            $query->where('status', 'requested');
        }

        return view('lab.orders.index', [
            'orders' => $query->latest('ordered_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(TestOrder $order): View
    {
        $order->load(['patient', 'doctor.user', 'items.test', 'samples.receiver']);

        return view('lab.orders.show', compact('order'));
    }
}