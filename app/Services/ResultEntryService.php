<?php

namespace App\Services;

use App\Domain\Critical\CriticalValueEngine;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Models\CriticalAlert;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Result entry / submit orchestration. Classification delegated solely to CriticalValueEngine.
 */
class ResultEntryService
{
    public function __construct(
        private readonly CriticalValueEngine $engine,
        private readonly WorkflowTransitionService $workflow,
        private readonly AuditLogger $audit,
    ) {}

    public function saveDraft(TestOrderItem $item, array $data, User $actor): Result
    {
        return $this->persist($item, $data, $actor, submit: false);
    }

    public function submit(TestOrderItem $item, array $data, User $actor): Result
    {
        return $this->persist($item, $data, $actor, submit: true);
    }

    private function persist(TestOrderItem $item, array $data, User $actor, bool $submit): Result
    {
        $item->loadMissing(['order.doctor', 'test.referenceRanges', 'result']);
        $order = $item->order;
        $test = $item->test;

        if (! $test) {
            throw ValidationException::withMessages(['test_id' => 'الفحص غير موجود.']);
        }

        $existing = $item->result;
        if ($existing && ! $existing->isEditable() && $existing->status !== Result::STATUS_SUBMITTED) {
            // Allow editing only before approval (draft/returned). Submitted pending review is locked for tech edits.
            if (! in_array($existing->status, [Result::STATUS_DRAFT, Result::STATUS_RETURNED], true)) {
                throw new DomainException('لا يمكن تعديل النتيجة بعد اعتمادها أو أثناء انتظار المراجعة.');
            }
        }
        if ($existing && $existing->status === Result::STATUS_PENDING_REVIEW) {
            throw new DomainException('النتيجة قيد المراجعة ولا يمكن تعديلها.');
        }
        if ($existing && $existing->status === Result::STATUS_APPROVED) {
            throw new DomainException('النتيجة معتمدة ولا يمكن تعديلها.');
        }

        $value = $data['value_numeric'] ?? null;
        $unit = $data['unit'] ?? $test->unit;
        $notes = $data['notes'] ?? null;

        if ($submit) {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                throw ValidationException::withMessages(['value_numeric' => 'يجب إدخال قيمة رقمية صالحة قبل الإرسال للمراجعة.']);
            }
        } elseif ($value !== null && $value !== '' && ! is_numeric($value)) {
            throw ValidationException::withMessages(['value_numeric' => 'قيمة النتيجة غير صالحة.']);
        }

        $range = $test->referenceRanges->firstWhere('is_active', true)
            ?? $test->referenceRanges()->where('is_active', true)->orderByDesc('id')->first();

        $outcome = $this->engine->classify(
            $value !== null && $value !== '' ? $value : null,
            $range,
            $unit,
            $test->unit,
        );

        return DB::transaction(function () use ($item, $order, $test, $existing, $value, $unit, $notes, $actor, $submit, $outcome, $range) {
            $this->ensureAnalysisState($order);

            $payload = [
                'test_order_item_id' => $item->id,
                'test_order_id' => $order->id,
                'patient_id' => $order->patient_id,
                'test_id' => $test->id,
                'value_numeric' => ($value === null || $value === '') ? null : $value,
                'value_text' => null,
                'unit' => $unit,
                'classification' => $outcome->classification,
                'notes' => $notes,
                'updated_by' => $actor->id,
                'reference_range_id' => $outcome->referenceRangeId,
                'range_snapshot' => $outcome->thresholdSnapshot,
            ];

            if ($existing) {
                $old = $existing->only(['value_numeric', 'unit', 'notes', 'status', 'classification']);
                $existing->fill($payload);
                if ($existing->entered_by === null) {
                    $existing->entered_by = $actor->id;
                    $existing->entered_at = now();
                }
                if ($submit) {
                    $existing->status = Result::STATUS_PENDING_REVIEW;
                    $existing->submitted_at = $existing->submitted_at ?? now();
                } elseif ($existing->status === Result::STATUS_RETURNED) {
                    $existing->status = Result::STATUS_DRAFT;
                } else {
                    $existing->status = Result::STATUS_DRAFT;
                }
                $existing->save();
                $result = $existing->refresh();
                $this->audit->log(
                    $submit ? 'results.submitted' : 'results.edited',
                    $result,
                    $old,
                    $result->only(['value_numeric', 'unit', 'notes', 'status', 'classification']),
                    $actor->id,
                );
            } else {
                $payload['entered_by'] = $actor->id;
                $payload['entered_at'] = now();
                $payload['status'] = $submit ? Result::STATUS_PENDING_REVIEW : Result::STATUS_DRAFT;
                if ($submit) {
                    $payload['submitted_at'] = now();
                }
                $result = Result::query()->create($payload);
                $this->audit->log(
                    $submit ? 'results.submitted' : 'results.entered',
                    $result,
                    null,
                    $result->only(['value_numeric', 'unit', 'notes', 'status', 'classification']),
                    $actor->id,
                );
            }

            $item->update(['status' => $result->status]);

            if ($submit) {
                $this->syncCriticalAlert($result, $order, $outcome->isCritical);
                $this->maybeMoveOrderToPendingReview($order);
            }

            return $result->fresh(['test', 'criticalAlert', 'patient']);
        });
    }

    private function ensureAnalysisState(TestOrder $order): void
    {
        if ($order->status === OrderStatus::SAMPLE_RECEIVED) {
            // Stamps analysis_started_at via WorkflowTransitionService.
            $this->workflow->transition($order, OrderStatus::IN_ANALYSIS);
        } elseif (! in_array($order->status, [
            OrderStatus::IN_ANALYSIS,
            OrderStatus::PENDING_REVIEW,
            OrderStatus::DEFERRED,
        ], true) && $order->status !== OrderStatus::READY) {
            // Allow entry once sample received path has started; reject if still requested
            if ($order->status === OrderStatus::REQUESTED) {
                throw new DomainException('يجب استلام العينة قبل إدخال النتائج.');
            }
        }
    }

    private function maybeMoveOrderToPendingReview(TestOrder $order): void
    {
        $order->load('items.result');
        $allSubmitted = $order->items->every(function (TestOrderItem $item) {
            $result = $item->result;
            return $result && in_array($result->status, [
                Result::STATUS_PENDING_REVIEW,
                Result::STATUS_APPROVED,
            ], true);
        });

        if ($allSubmitted && OrderStatus::canTransition($order->status, OrderStatus::PENDING_REVIEW)) {
            $this->workflow->transition($order, OrderStatus::PENDING_REVIEW);
        }
    }

    private function syncCriticalAlert(Result $result, TestOrder $order, bool $isCritical): void
    {
        if (! $isCritical || $result->classification !== ResultClassification::CRITICAL) {
            return;
        }

        $alert = CriticalAlert::query()->firstOrNew(['result_id' => $result->id]);
        $isNew = ! $alert->exists;
        $alert->fill([
            'patient_id' => $result->patient_id,
            'test_order_id' => $result->test_order_id,
            'test_id' => $result->test_id,
            'doctor_id' => $order->doctor_id,
            'detected_value' => $result->value_numeric,
            'unit' => $result->unit,
            'threshold_snapshot' => $result->range_snapshot,
            'discovered_at' => $alert->discovered_at ?? now(),
            'status' => CriticalAlert::STATUS_OPEN,
        ]);
        $alert->save();

        if ($isNew) {
            $this->audit->log('critical.detected', $alert, null, [
                'result_id' => $result->id,
                'detected_value' => $result->value_numeric,
                'discovered_at' => $alert->discovered_at?->toIso8601String(),
            ]);

            // In-app doctor notify only — no external email/WhatsApp until approval.
            app(NotificationOrchestrator::class)->onCriticalDetected($alert->fresh());
        }
    }
}
