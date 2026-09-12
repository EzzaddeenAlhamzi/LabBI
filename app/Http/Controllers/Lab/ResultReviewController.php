<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\ResultReviewService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultReviewController extends Controller
{
    public function index(): View
    {
        $results = Result::with(['patient', 'test', 'order', 'enteredBy'])
            ->where('status', Result::STATUS_PENDING_REVIEW)
            ->latest('updated_at')
            ->paginate(20);

        return view('lab.results.review-index', compact('results'));
    }

    public function show(Result $result): View
    {
        $result->load([
            'patient',
            'test.referenceRanges',
            'order.doctor.user',
            'enteredBy',
            'reviews.reviewer',
            'criticalAlert',
            'attachments',
        ]);

        return view('lab.results.review-show', compact('result'));
    }

    public function approve(Request $request, Result $result, ResultReviewService $service): RedirectResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        try {
            $service->approve($result, $request->user(), $data['comment'] ?? null);
        } catch (DomainException $e) {
            return back()->withErrors(['comment' => $e->getMessage()]);
        }

        return redirect()->route('lab.results.review.index')->with('success', 'تم اعتماد النتيجة.');
    }

    public function returnResult(Request $request, Result $result, ResultReviewService $service): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        try {
            $service->returnForCorrection($result, $request->user(), $data['comment']);
        } catch (DomainException $e) {
            return back()->withErrors(['comment' => $e->getMessage()]);
        }

        return redirect()->route('lab.results.review.index')->with('success', 'أُعيدت النتيجة للتصحيح.');
    }

    public function reject(Request $request, Result $result, ResultReviewService $service): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        try {
            $service->reject($result, $request->user(), $data['comment']);
        } catch (DomainException $e) {
            return back()->withErrors(['comment' => $e->getMessage()]);
        }

        return redirect()->route('lab.results.review.index')->with('success', 'تم رفض النتيجة.');
    }
}
