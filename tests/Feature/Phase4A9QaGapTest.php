<?php

namespace Tests\Feature;

use App\Domain\Bi\BiReportFilters;
use App\Domain\Bi\BiTrend;
use App\Domain\Bi\TrendCalculator;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\GeneratedReport;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Bi\BIQueryService;
use App\Services\Bi\CriticalAnalyticsService;
use App\Services\Bi\HistoricalComparisonService;
use App\Services\Bi\TATService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A9 independent QA gaps for Phase 4 — metric correctness, comparison edge cases,
 * critical stored-data-only, and clinical E2E regression through BI surfaces.
 */
class Phase4A9QaGapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Independently compute expected BI card counts + TAT from controlled fixtures
     * and assert services + HTTP dashboard match (pages loading alone is insufficient).
     */
    public function test_a9_independent_metric_correctness_counts_tat_critical_trend(): void
    {
        $this->seedRolesAndPermissions();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();
        $dept = Department::first();

        // Controlled timeline (independent expected seconds calculated below).
        $orderedAt = Carbon::parse('2026-09-12 08:00:00');
        $receivedAt = Carbon::parse('2026-09-12 09:00:00');
        $analysisAt = Carbon::parse('2026-09-12 09:30:00');
        $submittedAt = Carbon::parse('2026-09-12 10:00:00');
        $approvedAt = Carbon::parse('2026-09-12 10:30:00');
        $completedAt = Carbon::parse('2026-09-12 12:00:00');

        $expectedOrderToSample = 3600;
        $expectedSampleToAnalysis = 1800;
        $expectedAnalysisToSubmit = 1800;
        $expectedSubmitToApproval = 1800;
        $expectedOrderTat = 14400; // 08:00 → 12:00
        $expectedCriticalHandling = 300; // 11:00 → 11:05

        $order = TestOrder::query()->create([
            'order_number' => 'ORD-A9-METRIC-1',
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
            'sample_number' => 'SMP-A9-1',
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
        CriticalAlert::query()->create([
            'result_id' => $result->id,
            'patient_id' => $patient->id,
            'test_order_id' => $order->id,
            'test_id' => $test->id,
            'doctor_id' => $doctor->id,
            'detected_value' => 5.0,
            'unit' => 'g/dL',
            'discovered_at' => Carbon::parse('2026-09-12 11:00:00'),
            'notified_at' => Carbon::parse('2026-09-12 11:05:00'),
            'status' => CriticalAlert::STATUS_OPEN,
        ]);

        // Extra deferred order today — card must reflect exact status counts.
        TestOrder::query()->create([
            'order_number' => 'ORD-A9-DEFER',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept?->id,
            'status' => OrderStatus::DEFERRED,
            'ordered_at' => Carbon::today()->setTime(7, 0),
            'created_by' => $admin->id,
        ]);

        // --- Independent expected card counts from DB ---
        $expectedCards = [
            'patients' => Patient::count(),
            'doctors' => Doctor::count(),
            'tests_catalog' => LabTest::where('is_active', true)->count(),
            'orders_today' => TestOrder::whereBetween('ordered_at', [Carbon::today(), Carbon::today()->endOfDay()])->count(),
            'samples_awaiting' => Sample::where('status', 'awaiting')->count(),
            'in_analysis' => TestOrder::where('status', OrderStatus::IN_ANALYSIS)->count(),
            'pending_review' => TestOrder::where('status', OrderStatus::PENDING_REVIEW)->count(),
            'ready_completed' => TestOrder::whereIn('status', [
                OrderStatus::READY, OrderStatus::COMPLETED, OrderStatus::ARCHIVED,
            ])->count(),
            'deferred' => TestOrder::where('status', OrderStatus::DEFERRED)->count(),
            'rejected' => TestOrder::where('status', OrderStatus::REJECTED)->count(),
            'critical_cases' => CriticalAlert::count(),
            'critical_open' => CriticalAlert::where('status', CriticalAlert::STATUS_OPEN)->count(),
        ];

        $this->assertSame(1, $expectedCards['deferred']);
        $this->assertSame(1, $expectedCards['critical_cases']);
        $this->assertGreaterThanOrEqual(1, $expectedCards['ready_completed']);

        $dashboard = app(BIQueryService::class)->dashboard();
        foreach ($expectedCards as $key => $expected) {
            $this->assertSame($expected, $dashboard['cards'][$key], "card {$key} mismatch");
        }

        // TAT intervals — independent vs service
        $tat = app(TATService::class)->orderIntervals($order->fresh(['samples', 'results']));
        $this->assertSame($expectedOrderToSample, $tat['intervals']['order_to_sample']['seconds']);
        $this->assertSame($expectedSampleToAnalysis, $tat['intervals']['sample_to_analysis']['seconds']);
        $this->assertSame($expectedAnalysisToSubmit, $tat['intervals']['analysis_to_submit']['seconds']);
        $this->assertSame($expectedSubmitToApproval, $tat['intervals']['submit_to_approval']['seconds']);
        $this->assertSame($expectedOrderTat, $tat['intervals']['order_tat']['seconds']);

        $perf = $dashboard['performance'];
        $this->assertSame($expectedOrderTat, $perf['avg_order_tat_seconds']);
        $this->assertSame($expectedCriticalHandling, $perf['avg_critical_handling_seconds']);
        $this->assertSame(1, $perf['critical_in_period']);

        // Trend independent math: 12 → 15 → rising (relative 3/15 = 0.2)
        $trend = (new TrendCalculator)->calculate([
            ['value' => 12.0, 'unit' => 'g/dL'],
            ['value' => 15.0, 'unit' => 'g/dL'],
        ]);
        $this->assertSame(BiTrend::RISING, $trend['trend']);
        $this->assertEqualsWithDelta(0.2, $trend['relative_change'], 0.0001);

        // HTTP must surface exact card values (not placeholders)
        $response = $this->actingAs($admin)->get(route('admin.bi.index'));
        $response->assertOk();
        foreach (['patients', 'doctors', 'deferred', 'critical_cases', 'ready_completed'] as $key) {
            $response->assertSee((string) $expectedCards[$key], false);
        }
        $response->assertSee('مؤشرات الأداء', false);
    }

    public function test_a9_comparison_date_filter_falling_stable_and_pending_excluded(): void
    {
        $this->seedRolesAndPermissions();
        $doctorUser = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();
        $doctor = $doctorUser->doctor;

        $this->seedApproved($patient, $test, $doctor, $admin, 15.0, Carbon::parse('2026-09-01 10:00:00'));
        $this->seedApproved($patient, $test, $doctor, $admin, 12.0, Carbon::parse('2026-09-10 10:00:00'));
        // Pending (submitted, not approved) must not appear
        $this->seedPending($patient, $test, $doctor, $admin, 99.0, Carbon::parse('2026-09-11 10:00:00'));

        $service = app(HistoricalComparisonService::class);

        $all = $service->compare($doctorUser, $patient, BiReportFilters::fromArray([
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]));
        $this->assertCount(2, $all['rows']);
        $this->assertSame(BiTrend::FALLING, $all['trend']['trend']);
        $this->assertSame(ResultClassification::NORMAL, $all['rows'][0]['classification']);
        $this->assertArrayHasKey('classification_label', $all['rows'][0]);

        // CMP-01 date range: only first point
        $filtered = $service->compare($doctorUser, $patient, BiReportFilters::fromArray([
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-05',
        ]));
        $this->assertCount(1, $filtered['rows']);
        $this->assertSame(BiTrend::INSUFFICIENT_DATA, $filtered['trend']['trend']);

        // UI: falling trend + classification labels (CMP-02/03)
        $this->actingAs($doctorUser)->get(route('doctor.comparison.index', [
            'patient_id' => $patient->id,
            'test_id' => $test->id,
        ]))->assertOk()
            ->assertSee('تنازلي')
            ->assertSee('طبيعي')
            ->assertSee('15')
            ->assertSee('12');

        $values = collect($all['rows'])->pluck('value')->map(fn ($v) => (float) $v)->all();
        $this->assertNotContains(99.0, $values);

        // Stable math (independent calculator) — CMP-03
        $stable = (new TrendCalculator)->calculate([
            ['value' => 100, 'unit' => 'mg/dL'],
            ['value' => 104, 'unit' => 'mg/dL'],
        ]);
        $this->assertSame(BiTrend::STABLE, $stable['trend']);
    }

    public function test_a9_critical_dashboard_consumes_stored_alerts_only(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::where('code', 'HB')->firstOrFail();

        // Critical classification WITHOUT a stored Phase-2 alert — must NOT invent a dashboard row.
        $order = TestOrder::query()->create([
            'order_number' => 'ORD-A9-NOALERT',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => OrderStatus::COMPLETED,
            'ordered_at' => now()->subHour(),
            'completed_at' => now(),
            'created_by' => $admin->id,
        ]);
        $item = $order->items()->create(['test_id' => $test->id, 'status' => Result::STATUS_APPROVED]);
        Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'value_numeric' => 4.0,
            'unit' => 'g/dL',
            'classification' => ResultClassification::CRITICAL,
            'status' => Result::STATUS_APPROVED,
            'entered_by' => $admin->id,
            'entered_at' => now(),
            'submitted_at' => now(),
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->assertSame(0, CriticalAlert::count());

        $dash = app(CriticalAnalyticsService::class)->dashboard(new BiReportFilters);
        $this->assertSame(0, $dash['summary']['total']);
        $this->assertSame(0, $dash['rows']->total());

        $this->actingAs($admin)->get(route('admin.critical-cases.index'))
            ->assertOk()
            ->assertSee('لوحة الحالات الحرجة');

        // Behavioral proof: critical classification alone never creates dashboard rows.
        $this->assertSame(0, CriticalAlert::count());
        $this->assertGreaterThan(0, Result::where('classification', ResultClassification::CRITICAL)->count());
    }

    /**
     * Full clinical E2E regression: order→sample→result→critical→approve→notify→PDF→visibility,
     * then assert BI/critical surfaces reflect stored state without breaking the flow.
     */
    public function test_a9_clinical_e2e_regression_with_bi_surfaces(): void
    {
        $this->seedRolesAndPermissions();
        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
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

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $this->assertSame(1, CriticalAlert::count());
        $alert = CriticalAlert::firstOrFail();
        $this->assertNotNull($alert->discovered_at);

        $result = Result::where('test_order_item_id', $item->id)->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $result = $result->fresh();
        $this->assertSame(Result::STATUS_APPROVED, $result->status);

        // Phase 3 surfaces still work (notify/PDF/visibility)
        $this->actingAs($doctor)->get(route('doctor.results.show', $result))->assertOk();
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))->assertOk();
        $this->actingAs($doctor)->get(route('notifications.index'))->assertOk();
        $report = GeneratedReport::where('result_id', $result->id)->firstOrFail();
        $this->actingAs($doctor)->get(route('reports.download', $report))->assertOk();

        // BI / critical / comparison consume post-approval state
        $bi = app(BIQueryService::class)->dashboard();
        $this->assertSame(CriticalAlert::count(), $bi['cards']['critical_cases']);
        $this->assertGreaterThanOrEqual(1, $bi['cards']['ready_completed']);

        $this->actingAs($admin)->get(route('admin.critical-cases.index'))
            ->assertOk()
            ->assertSee((string) $alert->detected_value);

        $compare = app(HistoricalComparisonService::class)->compare(
            $doctor,
            $patient,
            BiReportFilters::fromArray(['patient_id' => $patient->id, 'test_id' => $test->id]),
        );
        $this->assertCount(1, $compare['rows']);
        $this->assertSame(ResultClassification::CRITICAL, $compare['rows'][0]['classification']);
    }

    private function seedApproved(
        Patient $patient,
        LabTest $test,
        Doctor $doctor,
        User $admin,
        float $value,
        Carbon $approvedAt,
        string $unit = 'g/dL',
    ): Result {
        $order = TestOrder::query()->create([
            'order_number' => 'ORD-A9-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => OrderStatus::COMPLETED,
            'ordered_at' => $approvedAt->copy()->subDay(),
            'completed_at' => $approvedAt,
            'created_by' => $admin->id,
        ]);
        $item = $order->items()->create(['test_id' => $test->id, 'status' => Result::STATUS_APPROVED]);

        return Result::query()->create([
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

    private function seedPending(
        Patient $patient,
        LabTest $test,
        Doctor $doctor,
        User $admin,
        float $value,
        Carbon $at,
    ): Result {
        $order = TestOrder::query()->create([
            'order_number' => 'ORD-A9-PEND-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => OrderStatus::PENDING_REVIEW,
            'ordered_at' => $at->copy()->subDay(),
            'created_by' => $admin->id,
        ]);
        $item = $order->items()->create(['test_id' => $test->id, 'status' => Result::STATUS_PENDING_REVIEW]);

        return Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'value_numeric' => $value,
            'unit' => 'g/dL',
            'classification' => ResultClassification::NORMAL,
            'status' => Result::STATUS_PENDING_REVIEW,
            'entered_by' => $admin->id,
            'entered_at' => $at,
            'submitted_at' => $at,
        ]);
    }
}
