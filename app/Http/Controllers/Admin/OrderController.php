<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = TestOrder::with(['patient', 'doctor.user']);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                        ->orWhere('medical_file_number', 'like', "%{$search}%"));
            });
        }

        return view('admin.orders.index', [
            'orders' => $query->latest('ordered_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'patients' => Patient::orderBy('full_name')->get(),
            'doctors' => Doctor::with('user')->orderBy('id')->get(),
            'tests' => LabTest::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['exists:tests,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $order = TestOrder::create([
                'order_number' => $this->nextOrderNumber(),
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'status' => 'requested',
                'ordered_at' => now(),
                'created_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['test_ids'] as $testId) {
                TestOrderItem::create([
                    'test_order_id' => $order->id,
                    'test_id' => $testId,
                    'status' => 'requested',
                ]);
            }

            return $order;
        });

        $audit->log('orders.create', $order, null, [
            'order_number' => $order->order_number,
            'patient_id' => $order->patient_id,
            'doctor_id' => $order->doctor_id,
        ]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'تم إنشاء الطلب وإرساله للمختبر.');
    }

    public function show(TestOrder $order): View
    {
        $order->load(['patient', 'doctor.user', 'items.test', 'samples', 'creator']);

        return view('admin.orders.show', compact('order'));
    }

    private function nextOrderNumber(): string
    {
        $seq = TestOrder::count() + 1;

        return 'ORD-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}