<?php

namespace Tests\Feature;

use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Result;
use App\Models\ResultAttachment;
use App\Models\TestOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ac07_result_entry_draft_and_submit(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'notes' => 'مسودة',
            'action' => 'draft',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $this->assertSame(Result::STATUS_DRAFT, $result->status);
        $this->assertSame(ResultClassification::NORMAL, $result->classification);
        $this->assertDatabaseHas('audit_logs', ['action' => 'results.entered']);

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'notes' => 'إرسال',
            'action' => 'submit',
        ])->assertRedirect();

        $this->assertSame(Result::STATUS_PENDING_REVIEW, $result->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'results.submitted']);
        $this->assertSame(OrderStatus::PENDING_REVIEW, $order->fresh()->status);
    }

    public function test_ac08_normal_result_does_not_create_critical_alert(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $this->assertSame(ResultClassification::NORMAL, $result->classification);
        $this->assertSame(0, CriticalAlert::count());
    }

    public function test_ac08_ac09_ac10_ac12_critical_engine_creates_alert_with_fields(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $this->assertSame(ResultClassification::CRITICAL, $result->classification);
        $alert = CriticalAlert::firstOrFail();
        $this->assertSame($result->id, $alert->result_id);
        $this->assertSame($order->patient_id, $alert->patient_id);
        $this->assertSame($order->id, $alert->test_order_id);
        $this->assertSame($item->test_id, $alert->test_id);
        $this->assertSame($order->doctor_id, $alert->doctor_id);
        $this->assertEquals(5, (float) $alert->detected_value);
        $this->assertSame('g/dL', $alert->unit);
        $this->assertIsArray($alert->threshold_snapshot);
        $this->assertArrayHasKey('critical_low', $alert->threshold_snapshot);
        $this->assertArrayHasKey('critical_high', $alert->threshold_snapshot);
        $this->assertNotNull($alert->discovered_at);
        $this->assertSame(CriticalAlert::STATUS_OPEN, $alert->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'critical.detected']);

        $this->actingAs($lab)->get(route('lab.critical.index'))
            ->assertOk()
            ->assertSee((string) $alert->id);
    }

    public function test_ac13_approve_via_permission_not_lab_tech(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 13.5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();

        $this->actingAs($lab)->post(route('lab.results.approve', $result), [
            'comment' => 'محاولة فني',
        ])->assertForbidden();

        $this->actingAs($lab)->post(route('lab.results.return', $result), [
            'comment' => 'محاولة إعادة',
        ])->assertForbidden();

        $this->actingAs($lab)->post(route('lab.results.reject', $result), [
            'comment' => 'محاولة رفض',
        ])->assertForbidden();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result), [
            'comment' => 'معتمد',
        ])->assertRedirect();

        $this->assertSame(Result::STATUS_APPROVED, $result->fresh()->status);
        $this->assertNotNull($result->fresh()->approved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'results.approved']);
        $this->assertContains($order->fresh()->status, [OrderStatus::READY, OrderStatus::COMPLETED]);
    }

    public function test_ac07_edit_before_approval_writes_edited_audit(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 13,
            'unit' => 'g/dL',
            'notes' => 'أول مسودة',
            'action' => 'draft',
        ])->assertRedirect();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'notes' => 'تعديل',
            'action' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'results.edited']);
        $this->assertSame(14.0, (float) Result::firstOrFail()->value_numeric);
    }

    public function test_return_for_correction_and_reedit(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 15,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();

        $this->actingAs($admin)->post(route('lab.results.return', $result), [
            'comment' => 'أعد القياس',
        ])->assertRedirect();

        $this->assertSame(Result::STATUS_RETURNED, $result->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'results.returned']);
        $this->assertSame(OrderStatus::IN_ANALYSIS, $order->fresh()->status);

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $this->assertSame(Result::STATUS_PENDING_REVIEW, $result->fresh()->status);
    }

    public function test_server_validation_rejects_invalid_submit(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 'abc',
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertSessionHasErrors();
    }

    public function test_attachments_upload_download_acl_and_audit(): void
    {
        Storage::fake('local');
        [$lab, $order] = $this->orderReadyForEntry();

        $file = UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf');
        $this->actingAs($lab)->post(route('lab.attachments.store'), [
            'file' => $file,
            'test_order_id' => $order->id,
        ])->assertRedirect();

        $attachment = ResultAttachment::firstOrFail();
        $this->assertSame($order->patient_id, $attachment->patient_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attachments.uploaded']);

        $this->actingAs($lab)->get(route('lab.attachments.download', $attachment))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'attachments.downloaded']);

        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patient)->get(route('lab.attachments.index'))->assertForbidden();
    }

    public function test_reject_mime_not_allowed(): void
    {
        Storage::fake('local');
        [$lab, $order] = $this->orderReadyForEntry();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');
        $this->actingAs($lab)->post(route('lab.attachments.store'), [
            'file' => $file,
            'test_order_id' => $order->id,
        ])->assertSessionHasErrors('file');
    }

    public function test_doctor_and_patient_portal_visibility(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 6,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $this->actingAs($doctor)->get(route('doctor.results.index'))->assertOk()->assertSee($order->order_number);
        $this->actingAs($doctor)->get(route('doctor.critical.index'))->assertOk();
        $this->actingAs($doctor)->get(route('doctor.results.show', $result))->assertOk();

        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patientUser)->get(route('patient.tests.index'))->assertOk();
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))->assertOk()->assertSee((string) $result->value_numeric);
    }

    public function test_doctor_isolation_other_doctor_cannot_view_result(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'طبيب آخر',
            'email' => 'otherdoc@labbi.test',
            'password' => 'password123',
            'specialty' => 'باطنية',
        ])->assertRedirect();

        $otherDoctor = User::where('email', 'otherdoc@labbi.test')->firstOrFail();
        $this->actingAs($otherDoctor)->get(route('doctor.results.show', $result))->assertForbidden();
        $this->actingAs($otherDoctor)->get(route('doctor.results.index'))
            ->assertOk()
            ->assertDontSee($order->order_number);
    }

    public function test_patient_pending_result_hides_numeric_value(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14.25,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();

        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))
            ->assertOk()
            ->assertSee('حالة الفحص')
            ->assertDontSee('14.25');
    }

    public function test_patient_cannot_view_other_result(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ]);
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result));

        // Create second patient user without linking to this result
        $adminUser = $admin;
        $this->actingAs($adminUser)->post(route('admin.patients.store'), [
            'full_name' => 'مريض غريب',
            'phone' => '0550001111',
            'create_login' => 1,
            'email' => 'otherpatient@labbi.test',
            'password' => 'password123',
        ])->assertRedirect();

        $otherUser = User::where('email', 'otherpatient@labbi.test')->firstOrFail();
        $this->actingAs($otherUser)->get(route('patient.results.show', $result))->assertForbidden();
    }

    public function test_ac18_completed_order_can_archive_and_patient_sees_history(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $order = $order->fresh();
        $this->assertSame(OrderStatus::COMPLETED, $order->status);

        app(\App\Domain\Workflow\WorkflowTransitionService::class)
            ->transition($order, OrderStatus::ARCHIVED);
        $this->assertSame(OrderStatus::ARCHIVED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->archived_at);

        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patientUser)->get(route('patient.tests.index'))
            ->assertOk()
            ->assertSee($order->order_number);
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))
            ->assertOk()
            ->assertSee('14');
    }

    public function test_lab_portal_screens_render(): void
    {
        $lab = $this->userWithRole('lab_technician');
        $this->actingAs($lab)->get(route('lab.dashboard'))->assertOk()->assertSee('التنبيهات الحرجة');
        $this->actingAs($lab)->get(route('lab.results.entry.index'))->assertOk();
        $this->actingAs($lab)->get(route('lab.results.review.index'))->assertOk();
        $this->actingAs($lab)->get(route('lab.critical.index'))->assertOk();
        $this->actingAs($lab)->get(route('lab.attachments.index'))->assertOk();
        $this->actingAs($lab)->get(route('lab.completed.index'))->assertOk();
        $this->actingAs($lab)->get(route('lab.patients.search'))->assertOk();
    }

    public function test_cannot_enter_results_before_sample(): void
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $this->actingAs($doctor)->post(route('doctor.orders.store'), [
            'patient_id' => $patient->id,
            'test_ids' => [$test->id],
        ])->assertRedirect();
        $order = TestOrder::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'draft',
        ])->assertSessionHasErrors();
    }

    /**
     * @return array{0: User, 1: TestOrder, 2: \App\Models\TestOrderItem}
     */
    private function orderReadyForEntry(): array
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $this->actingAs($doctor)->post(route('doctor.orders.store'), [
            'patient_id' => $patient->id,
            'test_ids' => [$test->id],
        ])->assertRedirect();

        $order = TestOrder::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->actingAs($lab)->post(route('lab.samples.receive'), [
            'test_order_id' => $order->id,
            'test_order_item_id' => $item->id,
        ])->assertRedirect();

        return [$lab, $order->fresh(), $item->fresh()];
    }
}
