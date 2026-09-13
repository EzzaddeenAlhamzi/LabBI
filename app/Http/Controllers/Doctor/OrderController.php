<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Services\AuditLogger;
use App\Services\DoctorPatientAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $orders = TestOrder::with('patient')
            ->where('doctor_id', $doctor->id)
            ->latest('ordered_at')
            ->paginate(15);

        return view('doctor.orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $patients = Patient::query()
            ->where('primary_doctor_id', $doctor->id)
            ->orWhereHas('orders', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->orderBy('full_name')
            ->get();

        return view('doctor.orders.create', [
            'patients' => $patients,
            'tests' => LabTest::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit, DoctorPatientAccess $access): RedirectResponse
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 403);

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['exists:tests,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        // No open claim: unassigned patients require Admin primary_doctor assignment first.
        $access->assertCanAccess($doctor, $patient);

        $order = DB::transaction(function () use ($data, $request, $doctor) {
            $order = TestOrder::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad((string) (TestOrder::count() + 1), 4, '0', STR_PAD_LEFT),
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctor->id,
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

        $audit->log('orders.create', $order);

        return redirect()->route('doctor.orders.show', $order)->with('success', 'تم إنشاء طلب الفحص وإرساله للمختبر.');
    }

    public function show(Request $request, TestOrder $order): View
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor && $order->doctor_id === $doctor->id, 403);

        $order->load(['patient', 'items.test', 'samples']);

        return view('doctor.orders.show', compact('order'));
    }
}