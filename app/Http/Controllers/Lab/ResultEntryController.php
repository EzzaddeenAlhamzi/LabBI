<?php

namespace App\Http\Controllers\Lab;

use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Services\AuditLogger;
use App\Services\ResultEntryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultEntryController extends Controller
{
    public function index(): View
    {
        $orders = TestOrder::with(['patient', 'items.test', 'items.result'])
            ->whereIn('status', [
                OrderStatus::SAMPLE_RECEIVED,
                OrderStatus::IN_ANALYSIS,
                OrderStatus::PENDING_REVIEW,
                OrderStatus::DEFERRED,
            ])
            ->latest('ordered_at')
            ->paginate(20);

        return view('lab.results.entry-index', compact('orders'));
    }

    public function edit(TestOrder $order): View
    {
        $order->load(['patient', 'doctor.user', 'items.test.referenceRanges', 'items.result']);

        return view('lab.results.entry', compact('order'));
    }

    public function startAnalysis(TestOrder $order, WorkflowTransitionService $workflow, AuditLogger $audit): RedirectResponse
    {
        try {
            $from = $order->status;
            $workflow->transition($order, OrderStatus::IN_ANALYSIS);
            $audit->log('orders.start_analysis', $order, ['status' => $from], ['status' => $order->status]);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('lab.results.entry', $order)->with('success', 'تم بدء التحليل.');
    }

    public function save(Request $request, TestOrderItem $item, ResultEntryService $service): RedirectResponse
    {
        $data = $request->validate([
            'value_numeric' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'action' => ['required', 'in:draft,submit'],
        ]);

        try {
            if ($data['action'] === 'submit') {
                $service->submit($item, $data, $request->user());
                $msg = 'تم إرسال النتيجة للمراجعة.';
            } else {
                $service->saveDraft($item, $data, $request->user());
                $msg = 'تم حفظ المسودة.';
            }
        } catch (DomainException $e) {
            return back()->withErrors(['value_numeric' => $e->getMessage()])->withInput();
        }

        return redirect()->route('lab.results.entry', $item->test_order_id)->with('success', $msg);
    }
}
