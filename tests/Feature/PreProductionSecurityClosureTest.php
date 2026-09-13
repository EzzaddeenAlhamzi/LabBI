<?php

namespace Tests\Feature;

use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Models\User;
use App\Services\ResultReviewService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pre-production security closure — A8 Mediums M1–M5.
 */
class PreProductionSecurityClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_m1_reject_only_from_pending_review(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $service = app(ResultReviewService::class);

        $pending = $this->makeResult(Result::STATUS_PENDING_REVIEW);
        $service->reject($pending, $admin, 'رفض مشروع من بانتظار المراجعة');
        $this->assertSame(Result::STATUS_REJECTED, $pending->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'results.rejected']);

        foreach ([
            Result::STATUS_DRAFT,
            Result::STATUS_SUBMITTED,
            Result::STATUS_APPROVED,
            Result::STATUS_RETURNED,
            Result::STATUS_REJECTED,
        ] as $status) {
            $result = $this->makeResult($status);
            try {
                $service->reject($result, $admin, 'محاولة غير صالحة');
                $this->fail("Reject from {$status} must be denied");
            } catch (DomainException $e) {
                $this->assertSame('النتيجة ليست بانتظار المراجعة.', $e->getMessage());
            }
            $this->assertSame($status, $result->fresh()->status);
        }

        // HTTP path: approved result cannot be rejected
        $approved = $this->makeResult(Result::STATUS_APPROVED);
        $this->actingAs($admin)
            ->post(route('lab.results.reject', $approved), ['comment' => 'رفض معتمد'])
            ->assertSessionHasErrors('comment');
        $this->assertSame(Result::STATUS_APPROVED, $approved->fresh()->status);

        // Order-level matrix: ready/completed/archived cannot transition to rejected;
        // requested/sample_received/in_analysis/pending_review may reject.
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::REQUESTED, OrderStatus::REJECTED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::SAMPLE_RECEIVED, OrderStatus::REJECTED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::IN_ANALYSIS, OrderStatus::REJECTED));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::PENDING_REVIEW, OrderStatus::REJECTED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::READY, OrderStatus::REJECTED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::COMPLETED, OrderStatus::REJECTED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::ARCHIVED, OrderStatus::REJECTED));
    }

    public function test_m1_return_still_requires_pending_review(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $pending = $this->makeResult(Result::STATUS_PENDING_REVIEW);

        app(ResultReviewService::class)->returnForCorrection($pending, $admin, 'أعد التصحيح');
        $this->assertSame(Result::STATUS_RETURNED, $pending->fresh()->status);
    }

    public function test_m2_doctor_cannot_claim_unassigned_or_foreign_patient(): void
    {
        $this->seedRolesAndPermissions();
        $doctorA = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $doctorB = $this->createSecondDoctor();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $unassigned = Patient::query()->create([
            'medical_file_number' => 'MF-UNASSIGNED-01',
            'full_name' => 'مريض غير مخصص',
            'phone' => '0559999001',
            'primary_doctor_id' => null,
        ]);

        $foreign = Patient::query()->create([
            'medical_file_number' => 'MF-FOREIGN-01',
            'full_name' => 'مريض طبيب آخر',
            'phone' => '0559999002',
            'primary_doctor_id' => $doctorB->doctor->id,
        ]);

        // Unauthorized claim / order
        $this->actingAs($doctorA)->post(route('doctor.orders.store'), [
            'patient_id' => $unassigned->id,
            'test_ids' => [$test->id],
        ])->assertForbidden();
        $this->assertNull($unassigned->fresh()->primary_doctor_id);
        $this->assertSame(0, TestOrder::where('patient_id', $unassigned->id)->count());

        $this->actingAs($doctorA)->post(route('doctor.orders.store'), [
            'patient_id' => $foreign->id,
            'test_ids' => [$test->id],
        ])->assertForbidden();

        // Unauthorized view
        $this->actingAs($doctorA)->get(route('doctor.patients.show', $unassigned))->assertForbidden();
        $this->actingAs($doctorA)->get(route('doctor.patients.show', $foreign))->assertForbidden();

        // Authorized path still works (seed patient assigned to doctor A)
        $assigned = Patient::query()->where('primary_doctor_id', $doctorA->doctor->id)->firstOrFail();
        $this->actingAs($doctorA)->get(route('doctor.patients.show', $assigned))->assertOk();
        $this->actingAs($doctorA)->post(route('doctor.orders.store'), [
            'patient_id' => $assigned->id,
            'test_ids' => [$test->id],
            'notes' => 'طلب مصرّح',
        ])->assertRedirect();
        $this->assertTrue(
            TestOrder::query()
                ->where('patient_id', $assigned->id)
                ->where('doctor_id', $doctorA->doctor->id)
                ->exists()
        );
    }

    public function test_m3_role_change_is_audited_with_old_new_roles_and_permissions(): void
    {
        $admin = $this->userWithRole('admin');
        $target = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $doctorRole = Role::where('code', 'doctor')->firstOrFail();
        $labRole = Role::where('code', 'lab_technician')->with('permissions')->firstOrFail();

        $oldPerms = $labRole->permissions->pluck('code')->sort()->values()->all();

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role_id' => $doctorRole->id,
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $log = AuditLog::query()->where('action', 'users.role_change')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($target->id, $log->auditable_id);
        $this->assertNotNull($log->created_at);
        $this->assertSame(['lab_technician'], $log->old_values['role_codes']);
        $this->assertSame(['doctor'], $log->new_values['role_codes']);
        $this->assertSame($oldPerms, $log->old_values['permission_codes']);
        $this->assertIsArray($log->new_values['permission_codes']);
        $this->assertNotSame($log->old_values['permission_codes'], $log->new_values['permission_codes']);
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $payload = json_encode([$log->old_values, $log->new_values]);
        $this->assertStringNotContainsString('password', strtolower((string) $payload));
    }

    public function test_m4_spoofed_forwarded_for_ignored_when_proxies_unconfigured(): void
    {
        // Default TRUSTED_PROXIES empty in testing → X-Forwarded-For must not become client IP.
        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.50',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/login')->assertOk();

        $this->assertSame('127.0.0.1', request()->ip());
        $this->assertFalse(request()->secure());
    }

    public function test_m5_restore_ui_shows_safe_arabic_error_not_exception_string(): void
    {
        $admin = $this->userWithRole('admin');
        $backup = Backup::query()->create([
            'status' => 'success',
            'disk' => 'local',
            'storage_path' => 'backups/missing-artifact-labbi.zip',
            'size_bytes' => 12,
            'taken_at' => now(),
            'created_by' => $admin->id,
            'trigger' => 'manual',
            'notes' => 'missing file for M5',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.backups.show', $backup))->post(
            route('admin.backups.restore', $backup),
            [
                'confirm_text' => 'RESTORE',
                'acknowledge' => '1',
            ]
        );

        $response->assertRedirect(route('admin.backups.show', $backup));
        $response->assertSessionHasErrors('restore');
        $message = session('errors')->get('restore')[0] ?? '';
        $this->assertStringContainsString('فشلت عملية الاستعادة', $message);
        $this->assertStringNotContainsString('Backup archive missing', $message);
        $this->assertStringNotContainsString('storage/', $message);
        $this->assertStringNotContainsString('.zip', $message);
        $this->assertStringNotContainsString('RuntimeException', $message);
        $this->assertStringNotContainsString('stack', strtolower($message));
    }

    private function createSecondDoctor(): User
    {
        $role = Role::where('code', 'doctor')->firstOrFail();
        $user = User::query()->create([
            'name' => 'طبيب ثانٍ',
            'email' => 'doctor2@labbi.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        Doctor::query()->create([
            'user_id' => $user->id,
            'license_number' => 'LIC-SEC-002',
            'specialty' => 'باطنية',
        ]);

        return $user->fresh(['doctor', 'roles']);
    }

    private function makeResult(string $status): Result
    {
        $patient = Patient::firstOrFail();
        $doctor = Doctor::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();

        $order = TestOrder::query()->create([
            'order_number' => 'ORD-SEC-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => OrderStatus::PENDING_REVIEW,
            'ordered_at' => now(),
            'created_by' => $admin->id,
        ]);

        $item = TestOrderItem::query()->create([
            'test_order_id' => $order->id,
            'test_id' => $test->id,
            'status' => $status,
        ]);

        Sample::query()->create([
            'test_order_id' => $order->id,
            'test_order_item_id' => $item->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'sample_number' => 'SMP-'.uniqid(),
            'status' => 'received',
            'received_at' => now(),
            'received_by' => $admin->id,
        ]);

        return Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'value_numeric' => 13.5,
            'unit' => 'g/dL',
            'classification' => ResultClassification::NORMAL,
            'status' => $status,
            'entered_by' => $admin->id,
            'entered_at' => now(),
            'submitted_at' => now(),
            'approved_by' => $status === Result::STATUS_APPROVED ? $admin->id : null,
            'approved_at' => $status === Result::STATUS_APPROVED ? now() : null,
        ]);
    }
}
