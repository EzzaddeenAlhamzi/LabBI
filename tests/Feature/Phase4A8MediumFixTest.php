<?php

namespace Tests\Feature;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Bi\BIQueryService;
use App\Services\Bi\CriticalAnalyticsService;
use App\Services\Bi\OperationalReportService;
use App\Services\Bi\TATService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * A8 Medium hardenings — Admin BI ACL OR-gates + bounded TAT/BI query windows.
 */
class Phase4A8MediumFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_tech_denied_admin_bi_surfaces_without_bi_view(): void
    {
        $this->seedRolesAndPermissions();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();

        $this->assertFalse($lab->hasPermission('bi.view'));
        $this->assertTrue($lab->hasPermission('critical.view_all'));
        $this->assertTrue($lab->hasPermission('patients.view_all'));

        $this->actingAs($lab)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($lab)->get(route('admin.bi.index'))->assertForbidden();
        $this->actingAs($lab)->get(route('admin.reports.operational'))->assertForbidden();
        $this->actingAs($lab)->get(route('admin.critical-cases.index'))->assertForbidden();

        // Lab operational critical surface remains available.
        $this->actingAs($lab)->get(route('lab.critical.index'))->assertOk();
    }

    public function test_doctor_and_patient_denied_admin_bi_urls(): void
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();

        foreach ([$doctor, $patient] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.bi.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.reports.operational'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.critical-cases.index'))->assertForbidden();
        }
    }

    public function test_doctor_cannot_compare_unrelated_patient_history(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'طبيب غير مرتبط',
            'email' => 'unrelated-a8@labbi.test',
            'password' => 'password123',
        ])->assertRedirect();

        $other = User::where('email', 'unrelated-a8@labbi.test')->firstOrFail();
        $this->actingAs($other)->get(route('doctor.comparison.index', [
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]))->assertOk()->assertSee('لا يمكنك');
    }

    public function test_admin_with_bi_view_retains_admin_bi_access(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->assertTrue($admin->hasPermission('bi.view'));

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.bi.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.operational'))->assertOk();
        $this->actingAs($admin)->get(route('admin.critical-cases.index'))->assertOk();
    }

    public function test_bi_filters_default_window_and_reject_invalid_or_excessive_ranges(): void
    {
        $default = BiReportFilters::fromArray([]);
        $this->assertNotNull($default->dateFrom);
        $this->assertNotNull($default->dateTo);
        $this->assertSame(
            BiReportFilters::DEFAULT_PERIOD_DAYS,
            (int) $default->dateFrom->copy()->startOfDay()->diffInDays($default->dateTo->copy()->startOfDay()) + 1,
        );

        $explicit = BiReportFilters::fromArray([
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-10',
        ]);
        $this->assertSame('2026-09-01', $explicit->dateFrom->toDateString());
        $this->assertSame('2026-09-10', $explicit->dateTo->toDateString());

        try {
            BiReportFilters::fromArray([
                'date_from' => '2026-09-10',
                'date_to' => '2026-09-01',
            ]);
            $this->fail('Expected ValidationException for inverted range');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date_from', $e->errors());
        }

        try {
            BiReportFilters::fromArray([
                'date_from' => '2024-01-01',
                'date_to' => '2026-09-01',
            ]);
            $this->fail('Expected ValidationException for excessive range');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date_to', $e->errors());
        }

        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->get(route('admin.bi.index', [
            'date_from' => '2024-01-01',
            'date_to' => '2026-09-01',
        ]))->assertSessionHasErrors('date_to');
    }

    public function test_sql_tat_aggregates_match_php_formulas_and_empty_dataset(): void
    {
        $this->seedRolesAndPermissions();
        $fixture = $this->makeCompletedOrderWithCritical();

        $phpOrder = app(TATService::class)->orderIntervals($fixture['order']->fresh(['samples', 'results']));
        $phpCritical = app(TATService::class)->criticalIntervals($fixture['alert']);

        $filters = BiReportFilters::fromArray([
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
        ]);

        $perf = app(BIQueryService::class)->performanceMetrics($filters);
        $this->assertSame($phpOrder['intervals']['order_tat']['seconds'], $perf['avg_order_tat_seconds']);
        $this->assertSame($phpCritical['handling_seconds'], $perf['avg_critical_handling_seconds']);
        $this->assertSame(1, $perf['orders_in_period']);
        $this->assertSame(1, $perf['critical_in_period']);
        $this->assertSame(1, $perf['tests_in_period']);

        $ops = app(OperationalReportService::class)->ordersReport($filters);
        $this->assertSame($phpOrder['intervals']['order_tat']['seconds'], $ops['summary']['avg_order_tat_seconds']);
        $this->assertSame(1, $ops['summary']['orders_count']);
        $this->assertTrue($ops['rows']->total() <= 25);

        $crit = app(CriticalAnalyticsService::class)->dashboard($filters);
        $this->assertSame($phpCritical['handling_seconds'], $crit['summary']['avg_handling_seconds']);
        $this->assertSame(1, $crit['summary']['total']);

        // Empty window — no hydrate blow-up; null averages.
        $empty = BiReportFilters::fromArray([
            'date_from' => '2020-01-01',
            'date_to' => '2020-01-31',
        ]);
        $emptyPerf = app(BIQueryService::class)->performanceMetrics($empty);
        $this->assertSame(0, $emptyPerf['orders_in_period']);
        $this->assertSame(0, $emptyPerf['critical_in_period']);
        $this->assertNull($emptyPerf['avg_order_tat_seconds']);
        $this->assertNull($emptyPerf['avg_critical_handling_seconds']);
    }

    public function test_default_reporting_window_excludes_orders_outside_period(): void
    {
        $this->seedRolesAndPermissions();
        Carbon::setTestNow(Carbon::parse('2026-09-13 12:00:00'));

        $this->makeCompletedOrderWithCritical(
            orderedAt: Carbon::parse('2026-09-12 08:00:00'),
            completedAt: Carbon::parse('2026-09-12 12:00:00'),
        );
        $this->makeCompletedOrderWithCritical(
            orderedAt: Carbon::parse('2025-01-01 08:00:00'),
            completedAt: Carbon::parse('2025-01-01 12:00:00'),
            orderNumber: 'ORD-A8-OLD',
            skipCritical: true,
        );

        $dashboard = app(BIQueryService::class)->dashboard();
        $this->assertSame(
            Carbon::today()->subDays(BiReportFilters::DEFAULT_PERIOD_DAYS - 1)->toDateString(),
            $dashboard['performance']['period']['from'],
        );
        $this->assertSame(1, $dashboard['performance']['orders_in_period']);
        $this->assertSame(14400, $dashboard['performance']['avg_order_tat_seconds']);

        Carbon::setTestNow();
    }

    /**
     * @return array{order: TestOrder, alert: CriticalAlert}
     */
    private function makeCompletedOrderWithCritical(
        ?Carbon $orderedAt = null,
        ?Carbon $completedAt = null,
        string $orderNumber = 'ORD-A8-TAT',
        bool $skipCritical = false,
    ): array {
        $admin = User::where('email', 'admin@labbi.local')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $patient = Patient::firstOrFail();
        $dept = Department::first();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        $orderedAt ??= Carbon::parse('2026-09-12 08:00:00');
        $completedAt ??= Carbon::parse('2026-09-12 12:00:00');
        $analysisAt = $orderedAt->copy()->addHours(1)->addMinutes(30);
        $receivedAt = $orderedAt->copy()->addHour();
        $submittedAt = $orderedAt->copy()->addHours(2);
        $approvedAt = $orderedAt->copy()->addHours(2)->addMinutes(30);

        $order = TestOrder::query()->create([
            'order_number' => $orderNumber.'-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept?->id,
            'status' => OrderStatus::COMPLETED,
            'ordered_at' => $orderedAt,
            'analysis_started_at' => $analysisAt,
            'completed_at' => $completedAt,
            'created_by' => $admin->id,
        ]);
        $item = $order->items()->create(['test_id' => $test->id, 'status' => Result::STATUS_APPROVED]);
        Sample::query()->create([
            'sample_number' => 'SMP-A8-'.uniqid(),
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_order_item_id' => $item->id,
            'test_id' => $test->id,
            'received_at' => $receivedAt,
            'received_by' => $admin->id,
            'status' => 'completed',
        ]);
        $result = Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'value_numeric' => 5.0,
            'unit' => 'g/dL',
            'classification' => ResultClassification::CRITICAL,
            'status' => Result::STATUS_APPROVED,
            'entered_by' => $admin->id,
            'entered_at' => $submittedAt,
            'submitted_at' => $submittedAt,
            'approved_by' => $admin->id,
            'approved_at' => $approvedAt,
        ]);

        $alert = null;
        if (! $skipCritical) {
            $alert = CriticalAlert::query()->create([
                'result_id' => $result->id,
                'patient_id' => $patient->id,
                'test_order_id' => $order->id,
                'test_id' => $test->id,
                'doctor_id' => $doctor->id,
                'detected_value' => 5.0,
                'unit' => 'g/dL',
                'discovered_at' => $orderedAt->copy()->addHours(3),
                'notified_at' => $orderedAt->copy()->addHours(3)->addMinutes(5),
                'status' => CriticalAlert::STATUS_OPEN,
            ]);
        }

        return [
            'order' => $order->fresh(['samples', 'results']),
            'alert' => $alert ?? CriticalAlert::make(),
        ];
    }
}
