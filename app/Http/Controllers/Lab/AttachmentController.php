<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\ResultAttachment;
use App\Models\TestOrder;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function index(Request $request): View
    {
        $attachments = ResultAttachment::with(['patient', 'order', 'result.test', 'uploader'])
            ->latest('uploaded_at')
            ->paginate(25);

        return view('lab.attachments.index', [
            'attachments' => $attachments,
            'orders' => TestOrder::with('patient')->latest('ordered_at')->limit(50)->get(),
        ]);
    }

    public function store(Request $request, AttachmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'result_id' => ['nullable', 'exists:results,id'],
            'test_order_id' => ['nullable', 'exists:test_orders,id'],
        ]);

        $result = isset($data['result_id']) ? Result::find($data['result_id']) : null;
        $order = isset($data['test_order_id']) ? TestOrder::find($data['test_order_id']) : null;

        $service->store($request->file('file'), $request->user(), $result, $order);

        return back()->with('success', 'تم رفع المرفق.');
    }

    public function download(ResultAttachment $attachment, Request $request, AttachmentService $service): StreamedResponse
    {
        return $service->download($attachment, $request->user());
    }
}
