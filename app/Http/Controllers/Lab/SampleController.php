<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Workflow\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SampleController extends Controller
{
    public function index(): View
    {
        return view('lab.samples.index', [
            'samples' => Sample::with(['patient', 'order', 'test'])->latest()->paginate(20),
        ]);
    }

    public function reception(): View
    {
        return view('lab.samples.reception', [
            'orders' => TestOrder::with(['patient', 'items.test'])
                ->whereIn('status', ['requested', 'sample_received'])
                ->latest('ordered_at')
                ->get(),
        ]);
    }

    public function receive(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'test_order_id' => ['required', 'exists:test_orders,id'],
            'test_order_item_id' => ['nullable', 'exists:test_order_items,id'],
            'sample_number' => ['nullable', 'string', 'max:50', 'unique:samples,sample_number'],
        ]);

        $order = TestOrder::with(['patient', 'items'])->findOrFail($data['test_order_id']);

        if (! empty($data['test_order_item_id'])) {
            $item = $order->items->firstWhere('id', (int) $data['test_order_item_id']);
            if (! $item) {
                return back()->withErrors(['test_order_item_id' => 'عنصر الطلب غير مرتبط بهذا الطلب (عدم تطابق).']);
            }
        } else {
            $item = $order->items->first();
        }

        $sample = DB::transaction(function () use ($data, $order, $item, $request) {
            $sample = Sample::create([
                'sample_number' => $data['sample_number'] ?? $this->nextSampleNumber(),
                'test_order_id' => $order->id,
                'patient_id' => $order->patient_id,
                'test_order_item_id' => $item?->id,
                'test_id' => $item?->test_id,
                'received_at' => now(),
                'received_by' => $request->user()->id,
                'status' => 'received',
            ]);

            if (OrderStatus::canTransition($order->status, OrderStatus::SAMPLE_RECEIVED)) {
                $order->update(['status' => OrderStatus::SAMPLE_RECEIVED]);
            }

            return $sample;
        });

        $audit->log('samples.receive', $sample, null, [
            'sample_number' => $sample->sample_number,
            'order_id' => $order->id,
            'received_by' => $request->user()->id,
        ]);

        return redirect()->route('lab.samples.index')->with('success', 'تم تأكيد استلام العينة ومطابقتها مع الطلب.');
    }

    public function reject(Request $request, Sample $sample, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $old = $sample->only(['status', 'rejection_reason']);
        $sample->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
        ]);
        $audit->log('samples.reject', $sample, $old, $sample->only(['status', 'rejection_reason']));

        return back()->with('success', 'تم رفض العينة.');
    }

    private function nextSampleNumber(): string
    {
        return 'SMP-'.now()->format('Ymd').'-'.str_pad((string) (Sample::count() + 1), 4, '0', STR_PAD_LEFT);
    }
}