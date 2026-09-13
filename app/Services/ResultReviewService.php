<?php

namespace App\Services;

use App\Contracts\ResultApprovalContract;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Jobs\ProcessResultApprovedJob;
use App\Models\Result;
use App\Models\ResultReview;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Review/approval gated solely by ResultApprovalContract (results.approve / results.return).
 * Never checks role names.
 * Post-approval communications are dispatched via ProcessResultApprovedJob → NotificationOrchestrator.
 */
class ResultReviewService
{
    public function __construct(
        private readonly ResultApprovalContract $approval,
        private readonly WorkflowTransitionService $workflow,
        private readonly AuditLogger $audit,
    ) {}

    public function approve(Result $result, User $reviewer, ?string $comment = null): Result
    {
        if (! $this->approval->canApprove($reviewer->id)) {
            throw new DomainException('غير مصرح باعتماد النتائج.');
        }

        if ($result->status !== Result::STATUS_PENDING_REVIEW) {
            throw new DomainException('النتيجة ليست بانتظار المراجعة.');
        }

        if ($result->classification === ResultClassification::UNKNOWN) {
            throw ValidationException::withMessages([
                'classification' => 'لا يمكن اعتماد نتيجة غير مصنّفة (REV-04).',
            ]);
        }

        return DB::transaction(function () use ($result, $reviewer, $comment) {
            $old = $result->only(['status', 'approved_by', 'approved_at']);
            $result->update([
                'status' => Result::STATUS_APPROVED,
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
            ]);
            $result->orderItem?->update(['status' => Result::STATUS_APPROVED]);

            ResultReview::query()->create([
                'result_id' => $result->id,
                'reviewer_id' => $reviewer->id,
                'action' => 'approve',
                'comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $this->audit->log('results.approved', $result, $old, [
                'status' => $result->status,
                'approved_by' => $reviewer->id,
                'approved_at' => $result->approved_at?->toIso8601String(),
                'comment' => $comment,
            ], $reviewer->id);

            $this->refreshOrderAfterReview($result->order()->first());

            $approved = $result->refresh();
            ProcessResultApprovedJob::dispatch($approved->id, $reviewer->id);

            return $approved;
        });
    }

    public function returnForCorrection(Result $result, User $reviewer, string $comment): Result
    {
        if (! $this->approval->canReturn($reviewer->id)) {
            throw new DomainException('غير مصرح بإعادة النتائج.');
        }

        if ($result->status !== Result::STATUS_PENDING_REVIEW) {
            throw new DomainException('النتيجة ليست بانتظار المراجعة.');
        }

        if (trim($comment) === '') {
            throw ValidationException::withMessages(['comment' => 'ملاحظات المراجع مطلوبة عند الإعادة.']);
        }

        return DB::transaction(function () use ($result, $reviewer, $comment) {
            $old = $result->only(['status']);
            $result->update(['status' => Result::STATUS_RETURNED]);
            $result->orderItem?->update(['status' => Result::STATUS_RETURNED]);

            ResultReview::query()->create([
                'result_id' => $result->id,
                'reviewer_id' => $reviewer->id,
                'action' => 'return',
                'comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $this->audit->log('results.returned', $result, $old, [
                'status' => $result->status,
                'comment' => $comment,
            ], $reviewer->id);

            $order = $result->order()->first();
            if ($order && OrderStatus::canTransition($order->status, OrderStatus::IN_ANALYSIS)) {
                $this->workflow->transition($order, OrderStatus::IN_ANALYSIS, 'أُعيدت نتائج للتصحيح');
            }

            return $result->refresh();
        });
    }

    /**
     * Result review transition matrix (server-enforced; UI alone is insufficient):
     *
     * | From (result)     | approve | return | reject |
     * |-------------------|---------|--------|--------|
     * | pending_review    | ✓       | ✓      | ✓      |
     * | draft             | ✗       | ✗      | ✗      |
     * | submitted         | ✗       | ✗      | ✗      |
     * | approved          | ✗       | ✗      | ✗      |
     * | returned          | ✗       | ✗      | ✗      |
     * | rejected          | ✗       | ✗      | ✗      |
     *
     * Order-level reject remains gated by OrderStatus::canTransition
     * (allowed from requested/sample_received/in_analysis/pending_review only —
     * not from ready/completed/archived).
     */
    public function reject(Result $result, User $reviewer, string $comment): Result
    {
        if (! $this->approval->canApprove($reviewer->id)) {
            throw new DomainException('غير مصرح برفض النتائج.');
        }

        if ($result->status !== Result::STATUS_PENDING_REVIEW) {
            throw new DomainException('النتيجة ليست بانتظار المراجعة.');
        }

        if (trim($comment) === '') {
            throw ValidationException::withMessages(['comment' => 'سبب الرفض مطلوب.']);
        }

        return DB::transaction(function () use ($result, $reviewer, $comment) {
            $old = $result->only(['status']);
            $result->update(['status' => Result::STATUS_REJECTED]);
            $result->orderItem?->update(['status' => Result::STATUS_REJECTED]);

            ResultReview::query()->create([
                'result_id' => $result->id,
                'reviewer_id' => $reviewer->id,
                'action' => 'reject',
                'comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $this->audit->log('results.rejected', $result, $old, [
                'status' => $result->status,
                'comment' => $comment,
            ], $reviewer->id);

            $order = $result->order()->first();
            if ($order && OrderStatus::canTransition($order->status, OrderStatus::REJECTED)) {
                $this->workflow->transition($order, OrderStatus::REJECTED, $comment);
            }

            return $result->refresh();
        });
    }

    private function refreshOrderAfterReview(?TestOrder $order): void
    {
        if (! $order) {
            return;
        }

        $order->load('items.result');
        $allApproved = $order->items->every(function (TestOrderItem $item) {
            return $item->result && $item->result->status === Result::STATUS_APPROVED;
        });

        if (! $allApproved) {
            return;
        }

        if (OrderStatus::canTransition($order->status, OrderStatus::READY)) {
            $this->workflow->transition($order, OrderStatus::READY);
        }

        // Phase 3: COMPLETED/ARCHIVED happen in NotificationOrchestrator after delivery kickoff.
        // If not all items approved yet, leave order at pending_review/ready as appropriate.
    }
}
