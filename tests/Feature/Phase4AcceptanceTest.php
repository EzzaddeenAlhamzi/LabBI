<?php

namespace Tests\Feature;

use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Bi\BIQueryService;
use App\Services\Bi\HistoricalComparisonService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ac20_admin_bi_cards_are_db_backed(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $dashboard = app(BIQueryService::class)->dashboard();

        $this->assertSame(Patient::count(), $dashboard['cards']['patients']);
        $this->assertGreaterThanOrEqual(1, $dashboard['cards']['doctors']);
        $this->assertGreaterThanOrEqual(1, $dashboard['cards']['tests_catalog']);
        $this->assertGreaterThanOrEqual(1, $dashboard['cards']['orders_today']);
        $this->assertArrayHasKey('in_analysis', $dashboard['cards']);
        $this->assertArrayHasKey('pending_review', $dashboard['cards']);
        $this->assertArrayHasKey('ready_completed', $dashboard['cards']);
        $this->assertArrayHasKey('deferred', $dashboard['cards']);
        $this->assertArrayHasKey('rejected', $dashboard['cards']);
        $this->assertArrayHasKey('critical_cases', $dashboard['cards']);
        $this->assertArrayHasKey('samples_awaiting', $dashboard['cards']);

        $this->actingAs($admin)->get(route('admin.bi.index'))->assertOk()
            ->assertSee('لوحة ذكاء الأعمال')
            ->assertSee((string) $dashboard['cards']['patients']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('مؤشرات تشغيلية');
    }

    public function test_ac20_performance_and_tat_on_completed_order(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $order->update([
            'ordered_at' => Carbon::parse('2026-09-12 08:00:00'),
        ]);

        $this->actingAs($lab)->post(route('lab.results.start_analysis', $order))->assertRedirect();
        $this->assertNotNull($order->fresh()->analysis_started_at);

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $this->assertNotNull($result->submitted_at);

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $order = $order->fresh();
        $this->assertTrue(in_array($order->status, [
            OrderStatus::READY,
            OrderStatus::COMPLETED,
            OrderStatus::ARCHIVED,
        ], true));
        $this->assertNotNull($order->completed_at ?? $result->fresh()->approved_at);

        $this->actingAs($admin)->get(route('admin.bi.index'))->assertOk()
            ->assertSee('مؤشرات الأداء');
    }

    public function test_operational_reports_server_filters_and_acl(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->get(route('admin.reports.operational', [
            'status' => OrderStatus::PENDING_REVIEW,
        ]))->assertOk()->assertSee($order->order_number);

        $this->actingAs($admin)->get(route('admin.reports.operational', [
            'status' => 'not-a-real-status',
        ]))->assertSessionHasErrors('status');

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $this->actingAs($doctor)->get(route('admin.reports.operational'))->assertForbidden();

        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patient)->get(route('admin.bi.index'))->assertForbidden();

        $labUser = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $this->actingAs($labUser)->get(route('admin.bi.index'))->assertForbidden();
    }

    public function test_critical_cases_dashboard_uses_stored_alerts_only(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $this->assertSame(1, CriticalAlert::count());
        $alert = CriticalAlert::firstOrFail();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->get(route('admin.critical-cases.index'))->assertOk()
            ->assertSee('لوحة الحالات الحرجة')
            ->assertSee((string) $alert->detected_value);

        $this->actingAs($admin)->get(route('admin.critical-cases.index', [
            'status' => CriticalAlert::STATUS_OPEN,
            'test_id' => $alert->test_id,
        ]))->assertOk();
    }

    public function test_ac19_doctor_historical_comparison_and_trend(): void
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $this->createApprovedResult($patient, $test, 12.0, Carbon::parse('2026-09-01 10:00:00'));
        $this->createApprovedResult($patient, $test, 15.0, Carbon::parse('2026-09-10 10:00:00'));

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $patient = Patient::firstOrFail();

        $this->actingAs($doctor)->get(route('doctor.comparison.index', [
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]))->assertOk()
            ->assertSee('المقارنة التاريخية')
            ->assertSee('تصاعدي')
            ->assertSee('12')
            ->assertSee('15')
            ->assertSee('غير تشخيصي');

        $service = app(HistoricalComparisonService::class);
        $compare = $service->compare($doctor, $patient, \App\Domain\Bi\BiReportFilters::fromArray([
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]));
        $this->assertCount(2, $compare['rows']);
        $this->assertSame('rising', $compare['trend']['trend']);
        $this->assertSame(ResultClassification::NORMAL, $compare['rows'][0]['classification']);
    }

    public function test_comparison_acl_blocks_unrelated_doctor_and_patient(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();
        $this->createApprovedResult($patient, $test, 13.0, now());

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'طبيب غريب',
            'email' => 'strangedoc-p4@labbi.test',
            'password' => 'password123',
        ])->assertRedirect();

        $otherDoctor = User::where('email', 'strangedoc-p4@labbi.test')->firstOrFail();
        $this->actingAs($otherDoctor)->get(route('doctor.comparison.index', [
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]))->assertOk()->assertSee('لا يمكنك');

        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patientUser)->get(route('doctor.comparison.index'))->assertForbidden();
    }

    public function test_comparison_incompatible_units_handled_safely(): void
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        // Persist two approved rows with conflicting units (bypass entry unit normalization).
        foreach ([
            [12.0, 'g/dL', Carbon::parse('2026-09-01 10:00:00')],
            [7.4, 'mmol/L', Carbon::parse('2026-09-10 10:00:00')],
        ] as [$value, $unit, $approvedAt]) {
            $order = TestOrder::query()->create([
                'order_number' => 'ORD-UNIT-'.uniqid(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->doctor->id,
                'status' => OrderStatus::COMPLETED,
                'ordered_at' => $approvedAt->copy()->subDay(),
                'completed_at' => $approvedAt,
                'created_by' => $admin->id,
            ]);
            $item = $order->items()->create(['test_id' => $test->id, 'status' => Result::STATUS_APPROVED]);
            Result::query()->create([
                'test_order_item_id' => $item->id,
                'test_order_id' => $order->id,
                'patient_id' => $patient->id,
                'test_id' => $test->id,
                'value_numeric' => $value,
                'unit' => $unit,
                'classification' => ResultClassification::NORMAL,
                'status' => Result::STATUS_APPROVED,
                'entered_by' => $admin->id,
                'entered_at' => $approvedAt,
                'submitted_at' => $approvedAt,
                'approved_by' => $admin->id,
                'approved_at' => $approvedAt,
            ]);
        }

        $compare = app(HistoricalComparisonService::class)->compare(
            $doctor->fresh(),
            $patient,
            \App\Domain\Bi\BiReportFilters::fromArray([
                'patient_id' => $patient->id,
                'test_id' => $test->id,
            ]),
        );

        $this->assertCount(2, $compare['rows']);
        $this->assertSame('incompatible_units', $compare['trend']['trend']);
        $this->assertNotEmpty($compare['limitations']);
    }

    public function test_draft_results_excluded_from_comparison(): void
    {
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'draft',
        ])->assertRedirect();

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $patient = Patient::findOrFail($order->patient_id);
        $compare = app(HistoricalComparisonService::class)->compare(
            $doctor,
            $patient,
            \App\Domain\Bi\BiReportFilters::fromArray([
                'patient_id' => $patient->id,
                'test_id' => $item->test_id,
            ]),
        );
        $this->assertCount(0, $compare['rows']);
    }

    public function test_arabic_rtl_bi_screens_render(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();

        $this->actingAs($admin)->get(route('admin.bi.index'))->assertOk()->assertSee('dir="rtl"', false);
        $this->actingAs($admin)->get(route('admin.reports.operational'))->assertOk()->assertSee('التقارير التشغيلية');
        $this->actingAs($admin)->get(route('admin.critical-cases.index'))->assertOk()->assertSee('الحالات الحرجة');
        $this->actingAs($doctor)->get(route('doctor.comparison.index'))->assertOk()->assertSee('المقارنة التاريخية');
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

    private function createApprovedResult(
        Patient $patient,
        LabTest $test,
        float $value,
        Carbon $approvedAt,
        string $unit = 'g/dL',
    ): Result {
        // Do not re-seed here — callers seed once; re-seeding would wipe prior results.
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $patient = Patient::findOrFail($patient->id);
        $test = LabTest::findOrFail($test->id);

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

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => $value,
            'unit' => $unit,
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::where('test_order_item_id', $item->id)->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $result->update(['approved_at' => $approvedAt, 'unit' => $unit, 'value_numeric' => $value]);

        return $result->fresh();
    }
}
