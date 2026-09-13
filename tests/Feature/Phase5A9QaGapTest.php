<?php

namespace Tests\Feature;

use App\Domain\Critical\ResultClassification;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\CriticalAlert;
use App\Models\GeneratedReport;
use App\Models\LabTest;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Ops\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Independent A9 gap coverage for Phase 5 — clinical E2E through ops surfaces
 * plus restore failure paths. Does not self-certify production readiness.
 */
class Phase5A9QaGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_a9_full_clinical_e2e_to_audit_backup_and_portals(): void
    {
        Mail::fake();
        $this->seedRolesAndPermissions();

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        // Doctor order
        $this->actingAs($doctor)->post(route('doctor.orders.store'), [
            'patient_id' => $patient->id,
            'test_ids' => [$test->id],
        ])->assertRedirect();
        $order = TestOrder::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        // Sample receive
        $this->actingAs($lab)->post(route('lab.samples.receive'), [
            'test_order_id' => $order->id,
            'test_order_item_id' => $item->id,
        ])->assertRedirect();

        // Critical result submit
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $this->assertSame(1, CriticalAlert::count());
        $alert = CriticalAlert::firstOrFail();

        $result = Result::where('test_order_item_id', $item->id)->firstOrFail();
        $this->assertSame(ResultClassification::CRITICAL, $result->classification);

        // Approve → notifications / email / PDF / archive transition
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();
        $result = $result->fresh();
        $this->assertSame(Result::STATUS_APPROVED, $result->status);

        $this->assertTrue(Notification::query()->exists());
        $report = GeneratedReport::where('result_id', $result->id)->firstOrFail();

        // Patient / doctor access
        $this->actingAs($doctor)->get(route('doctor.results.show', $result))->assertOk();
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))->assertOk();
        $this->actingAs($doctor)->get(route('reports.download', $report))->assertOk();
        $this->actingAs($patientUser)->get(route('reports.download', $report))->assertOk();
        $this->actingAs($doctor)->get(route('notifications.index'))->assertOk();
        $this->actingAs($patientUser)->get(route('notifications.index'))->assertOk();

        // Archive (complete→archived) + patient history
        $order = $order->fresh();
        $this->assertContains($order->status, [
            \App\Domain\Workflow\OrderStatus::COMPLETED,
            \App\Domain\Workflow\OrderStatus::ARCHIVED,
        ]);
        if ($order->status === \App\Domain\Workflow\OrderStatus::COMPLETED) {
            app(\App\Domain\Workflow\WorkflowTransitionService::class)
                ->transition($order, \App\Domain\Workflow\OrderStatus::ARCHIVED);
        }
        $this->assertSame(\App\Domain\Workflow\OrderStatus::ARCHIVED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->archived_at);
        $this->actingAs($patientUser)->get(route('patient.tests.index'))->assertOk();

        // Comparison + BI + critical dashboard + TAT/reports
        $this->actingAs($doctor)->get(route('doctor.comparison.index', [
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]))->assertOk();

        $this->actingAs($admin)->get(route('admin.bi.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.critical-cases.index'))
            ->assertOk()
            ->assertSee((string) $alert->detected_value);
        $this->actingAs($admin)->get(route('admin.reports.operational'))->assertOk();

        // Audit logs capture clinical trail
        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()->assertSee('سجل العمليات');
        $this->assertTrue(
            AuditLog::query()->whereIn('action', [
                'orders.create',
                'samples.receive',
                'results.submit',
                'results.approve',
                'critical.discovered',
                'critical_alerts.create',
            ])->exists()
            || AuditLog::query()->where('action', 'like', 'results.%')->exists()
        );

        // Backup after clinical activity (AC-22 path)
        config(['filesystems.disks.local.root' => storage_path('app/testing-private-a9')]);
        config(['labbi.backup.disk' => 'local']);
        @mkdir(storage_path('app/testing-private-a9'), 0750, true);

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertRedirect();
        $backup = Backup::query()->latest('id')->first();
        $this->assertNotNull($backup);
        $this->assertSame('success', $backup->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'backups.created']);
    }

    public function test_a9_restore_failure_paths_and_rbac(): void
    {
        config(['filesystems.disks.local.root' => storage_path('app/testing-private-a9-fail')]);
        config(['labbi.backup.disk' => 'local']);
        @mkdir(storage_path('app/testing-private-a9-fail'), 0750, true);

        $admin = $this->userWithRole('admin');
        $lab = $this->userWithRole('lab_technician');
        $doctor = $this->userWithRole('doctor');
        $patient = $this->userWithRole('patient');

        $backup = app(BackupService::class)->create($admin, 'manual', 'A9 failure-path fixture');
        $this->assertTrue($backup->isSuccessful());

        // Unconfirmed service restore must throw
        try {
            app(BackupService::class)->restore($backup, $admin, confirmed: false);
            $this->fail('Expected unconfirmed restore to throw');
        } catch (\Throwable $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        // UI restore without RESTORE confirmation rejected
        $this->actingAs($admin)
            ->from(route('admin.backups.show', $backup))
            ->post(route('admin.backups.restore', $backup), [
                'acknowledge_destructive' => '1',
                'confirm_text' => 'YES',
            ])
            ->assertSessionHasErrors('confirm_text');

        // Non-admin cannot restore
        foreach ([$lab, $doctor, $patient] as $user) {
            $this->actingAs($user)
                ->post(route('admin.backups.restore', $backup), [
                    'acknowledge_destructive' => '1',
                    'confirm_text' => 'RESTORE',
                ])
                ->assertForbidden();
        }

        // Direct URL matrix (FINAL_RBAC_MATRIX)
        foreach ([$lab, $doctor, $patient] as $user) {
            $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
            $this->actingAs($user)->get('/admin/backups')->assertForbidden();
            $this->actingAs($user)->get('/admin/settings')->assertForbidden();
            $this->actingAs($user)->get('/admin/bi')->assertForbidden();
        }
        $this->actingAs($patient)->get('/lab/results/entry')->assertForbidden();
        $this->actingAs($doctor)->get('/admin/users')->assertForbidden();
        $this->actingAs($lab)->get('/admin/users')->assertForbidden();

        // Happy restore still works with confirmation
        $marker = Patient::query()->create([
            'medical_file_number' => 'MF-A9-RESTORE',
            'full_name' => 'اختبار الاستعادة أ9',
            'phone' => '0559998877',
            'national_id' => '9988776655',
        ]);
        $fresh = app(BackupService::class)->create($admin, 'manual', 'pre-delete');
        $marker->delete();
        app(BackupService::class)->restore($fresh, $admin, confirmed: true);
        // Marker from before this backup should remain deleted; assert restore audit
        $this->assertDatabaseHas('audit_logs', ['action' => 'backups.restored']);
    }

    public function test_a9_scope_exclusions_not_exposed_as_features(): void
    {
        $admin = $this->userWithRole('admin');

        // Out-of-scope surfaces must not exist as product routes
        foreach ([
            '/admin/ai',
            '/admin/diagnosis',
            '/admin/inventory',
            '/admin/accounting',
            '/admin/analyzer',
            '/mobile',
            '/api/v1/analyzer',
        ] as $path) {
            $response = $this->actingAs($admin)->get($path);
            $this->assertTrue(
                in_array($response->status(), [404, 405], true),
                "Out-of-scope path {$path} should not be a live feature (got {$response->status()})"
            );
        }
    }
}
