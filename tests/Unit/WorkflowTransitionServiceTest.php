<?php

namespace Tests\Unit;

use App\Domain\Workflow\OrderStatus;
use App\Domain\Workflow\WorkflowTransitionService;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TestOrder;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_happy_path_transitions(): void
    {
        $this->seedRolesAndPermissions();
        $order = $this->makeOrder(OrderStatus::REQUESTED);
        $wf = app(WorkflowTransitionService::class);

        $wf->transition($order, OrderStatus::SAMPLE_RECEIVED);
        $wf->transition($order, OrderStatus::IN_ANALYSIS);
        $this->assertNotNull($order->fresh()->analysis_started_at);
        $wf->transition($order, OrderStatus::PENDING_REVIEW);
        $wf->transition($order, OrderStatus::READY);
        $wf->transition($order, OrderStatus::COMPLETED);
        $this->assertNotNull($order->fresh()->completed_at);
        $wf->transition($order, OrderStatus::ARCHIVED);

        $this->assertSame(OrderStatus::ARCHIVED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->archived_at);
    }

    public function test_rejects_illegal_skip(): void
    {
        $this->seedRolesAndPermissions();
        $order = $this->makeOrder(OrderStatus::REQUESTED);
        $this->expectException(DomainException::class);
        app(WorkflowTransitionService::class)->transition($order, OrderStatus::READY);
    }

    public function test_return_from_pending_review_to_analysis(): void
    {
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::PENDING_REVIEW, OrderStatus::IN_ANALYSIS));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::PENDING_REVIEW, OrderStatus::REJECTED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::COMPLETED, OrderStatus::READY));
    }

    private function makeOrder(string $status): TestOrder
    {
        $admin = User::where('email', 'admin@labbi.local')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $patient = Patient::firstOrFail();
        $dept = Department::first();

        return TestOrder::query()->create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept?->id,
            'status' => $status,
            'ordered_at' => now(),
            'created_by' => $admin->id,
        ]);
    }
}
