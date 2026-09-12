<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Workflow\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\TestOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = TestOrder::with(['patient', 'doctor.user', 'items.test']);
        $status = $request->string('status')->toString();

        if ($status === 'new') {
            $query->where('status', OrderStatus::REQUESTED);
        } elseif ($status !== '' && in_array($status, OrderStatus::all(), true)) {
            $query->where('status', $status);
        }

        return view('lab.orders.index', [
            'orders' => $query->latest('ordered_at')->paginate(20)->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function show(TestOrder $order): View
    {
        $order->load(['patient', 'doctor.user', 'items.test', 'items.result', 'samples.receiver', 'attachments']);

        return view('lab.orders.show', compact('order'));
    }
}
