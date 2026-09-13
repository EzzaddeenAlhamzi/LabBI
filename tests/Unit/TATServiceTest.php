<?php

namespace Tests\Unit;

use App\Models\CriticalAlert;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Bi\TATService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TATServiceTest extends TestCase
{
    use RefreshDatabase;

    private TATService $tat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tat = app(TATService::class);
        $this->seedRolesAndPermissions();
    }

    public function test_seconds_between_and_missing_never_invented(): void
    {
        $from = Carbon::parse('2026-09-12 08:00:00');
        $to = Carbon::parse('2026-09-12 10:00:00');
        $this->assertSame(7200, $this->tat->secondsBetween($from, $to));
        $this->assertNull($this->tat->secondsBetween(null, $to));
        $this->assertNull($this->tat->secondsBetween($from, null));
        $this->assertNull($this->tat->secondsBetween($to, $from));
    }

    public function test_order_intervals_from_real_timestamps(): void
    {
        $order = $this->makeOrderWithTimeline();

        $detail = $this->tat->orderIntervals($order->fresh(['samples', 'results']));

        $this->assertSame(3600, $detail['intervals']['order_to_sample']['seconds']);
        $this->assertSame(1800, $detail['intervals']['sample_to_analysis']['seconds']);
        $this->assertNotNull($detail['intervals']['order_tat']['seconds']);
        $this->assertSame(1800, $detail['intervals']['submit_to_approval']['seconds']);
        $this->assertEmpty(array_filter($detail['limitations']));
    }

    public function test_missing_analysis_started_reports_limitation(): void
    {
        $order = $this->makeOrderWithTimeline();
        $order->update(['analysis_started_at' => null]);

        $detail = $this->tat->orderIntervals($order->fresh(['samples', 'results']));
        $this->assertNull($detail['intervals']['sample_to_analysis']['seconds']);
        $this->assertNotEmpty($detail['limitations']);
        $this->assertStringContainsString('analysis_started_at', implode(' ', $detail['limitations']));
    }

    public function test_critical_handling_intervals(): void
    {
        $order = $this->makeOrderWithTimeline();
        $alert = CriticalAlert::query()->create([
            'result_id' => $order->results()->first()->id,
            'patient_id' => $order->patient_id,
            'test_order_id' => $order->id,
            'test_id' => $order->results()->first()->test_id,
            'doctor_id' => $order->doctor_id,
            'detected_value' => 5,
            'unit' => 'g/dL',
            'discovered_at' => Carbon::parse('2026-09-12 11:00:00'),
            'notified_at' => Carbon::parse('2026-09-12 11:05:00'),
            'acknowledged_at' => Carbon::parse('2026-09-12 11:20:00'),
            'status' => CriticalAlert::STATUS_ACKNOWLEDGED,
        ]);

        $detail = $this->tat->criticalIntervals($alert);
        $this->assertSame(300, $detail['discovery_to_notify_seconds']);
        $this->assertSame(900, $detail['notify_to_ack_seconds']);
        $this->assertSame(300, $detail['handling_seconds']);
    }

    public function test_aggregate_averages(): void
    {
        $order = $this->makeOrderWithTimeline();
        $agg = $this->tat->aggregate([$order->fresh(['samples', 'results'])]);
        $this->assertNotNull($agg['avg_order_tat_seconds']);
        $this->assertSame(1, $agg['sample_counts']['order_tat']);
    }

    private function makeOrderWithTimeline(): TestOrder
    {
        $admin = User::where('email', 'admin@labbi.local')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $patient = Patient::firstOrFail();
        $dept = Department::first();
        $test = \App\Models\LabTest::where('code', 'HB')->firstOrFail();

        $ordered = Carbon::parse('2026-09-12 08:00:00');
        $order = TestOrder::query()->create([
            'order_number' => 'ORD-TAT-'.uniqid(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $dept?->id,
            'status' => 'completed',
            'ordered_at' => $ordered,
            'analysis_started_at' => Carbon::parse('2026-09-12 09:30:00'),
            'completed_at' => Carbon::parse('2026-09-12 12:00:00'),
            'created_by' => $admin->id,
        ]);

        $item = $order->items()->create([
            'test_id' => $test->id,
            'status' => 'approved',
        ]);

        Sample::query()->create([
            'sample_number' => 'SMP-TAT-'.uniqid(),
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_order_item_id' => $item->id,
            'test_id' => $test->id,
            'received_at' => Carbon::parse('2026-09-12 09:00:00'),
            'received_by' => $admin->id,
            'status' => 'completed',
        ]);

        Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $patient->id,
            'test_id' => $test->id,
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'classification' => 'normal',
            'status' => Result::STATUS_APPROVED,
            'entered_by' => $admin->id,
            'entered_at' => Carbon::parse('2026-09-12 10:00:00'),
            'submitted_at' => Carbon::parse('2026-09-12 10:00:00'),
            'approved_by' => $admin->id,
            'approved_at' => Carbon::parse('2026-09-12 10:30:00'),
        ]);

        return $order->fresh(['samples', 'results']);
    }
}
