<?php

namespace Tests\Feature;

use App\Contracts\PdfReportGenerator;
use App\Contracts\WhatsAppGateway;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Jobs\DeliverEmailNotificationJob;
use App\Jobs\DeliverWhatsAppNotificationJob;
use App\Mail\DoctorResultApprovedMail;
use App\Models\CriticalAlert;
use App\Models\GeneratedReport;
use App\Models\LabTest;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Patient;
use App\Models\PatientRequest;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\NotificationOrchestrator;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ac14_pdf_rejected_for_unapproved(): void
    {
        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $this->expectException(\DomainException::class);
        app(PdfReportGenerator::class)->generateForApprovedResult($result);
    }

    public function test_ac14_pdf_generated_for_approved_only(): void
    {
        Storage::fake('local');
        [$lab, $order, $item] = $this->orderReadyForEntry();

        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $report = GeneratedReport::firstOrFail();
        $this->assertSame($result->id, $report->result_id);
        $this->assertSame($order->id, $report->test_order_id);
        $this->assertSame($order->patient_id, $report->patient_id);
        $this->assertStringStartsWith('reports/', $report->storage_key);
        $this->assertStringEndsWith('.pdf', $report->storage_key);
        Storage::disk('local')->assertExists($report->storage_key);
        $this->assertDatabaseHas('audit_logs', ['action' => 'pdf.generated']);

        // Idempotent — second generate returns same row
        $again = app(PdfReportGenerator::class)->generateForApprovedResult($result->fresh());
        $this->assertSame($report->id, $again->id);
        $this->assertSame(1, GeneratedReport::count());
    }

    public function test_ac15_doctor_accesses_approved_result_and_pdf_unrelated_blocked(): void
    {
        Storage::fake('local');
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $this->actingAs($doctor)->get(route('doctor.results.show', $result))->assertOk();
        $this->actingAs($doctor)->get(route('doctor.reports.index'))->assertOk();

        $report = GeneratedReport::firstOrFail();
        $this->actingAs($doctor)->get(route('reports.download', $report))->assertOk();

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'طبيب آخر',
            'email' => 'otherdoc-p3@labbi.test',
            'password' => 'password123',
            'specialty' => 'جراحة',
        ])->assertRedirect();
        $other = User::where('email', 'otherdoc-p3@labbi.test')->firstOrFail();
        $this->actingAs($other)->get(route('doctor.results.show', $result))->assertForbidden();
        $this->actingAs($other)->get(route('reports.download', $report))->assertForbidden();
    }

    public function test_ac16_email_sent_on_approve_not_on_unapproved_and_failure_logged(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();

        // No doctor email while pending
        Mail::assertNothingSent();
        $this->assertSame(0, NotificationDelivery::where('channel', 'email')->count());

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        Mail::assertSent(DoctorResultApprovedMail::class);
        $emailDelivery = NotificationDelivery::where('channel', 'email')->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_SENT, $emailDelivery->status);
        $this->assertNotNull($emailDelivery->sent_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email.sent']);

        // Unapproved blocked path
        $blocked = NotificationDelivery::query()->create([
            'notification_id' => $emailDelivery->notification_id,
            'channel' => NotificationDelivery::CHANNEL_EMAIL,
            'recipient' => 'block@labbi.test',
            'status' => NotificationDelivery::STATUS_PENDING,
            'attempts' => 0,
            'idempotency_key' => 'test-block-unapproved-email',
        ]);
        // Force result back conceptually by testing generator gate + job with draft clone scenario:
        $draftItem = $order->items()->first();
        // Use a separate pending result simulation via status mutation after clone of delivery on same notification
        // but mark result temporarily — instead create orphan delivery pointing at notification whose result we set returned
        $result->update(['status' => Result::STATUS_RETURNED]);
        (new DeliverEmailNotificationJob($blocked->id))->handle(app(\App\Services\AuditLogger::class));
        $this->assertSame(NotificationDelivery::STATUS_FAILED, $blocked->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email.delivery_blocked']);

        // Restore approved state for isolation — approval itself untouched by delivery failure
        $result->update(['status' => Result::STATUS_APPROVED]);
        $this->assertSame(Result::STATUS_APPROVED, $result->fresh()->status);
    }

    public function test_ac17_patient_readiness_whatsapp_and_inbox(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $patientUser = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $patientUser->id,
            'type' => Notification::TYPE_RESULT_READY,
            'result_id' => $result->id,
        ]);

        $wa = NotificationDelivery::where('channel', NotificationDelivery::CHANNEL_WHATSAPP)->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_SENT, $wa->status);
        $this->assertNotNull($wa->provider_ref);
        $this->assertDatabaseHas('audit_logs', ['action' => 'whatsapp.sent']);

        $this->actingAs($patientUser)->get(route('notifications.index'))->assertOk()->assertSee('نتيجتك جاهزة');
        $this->actingAs($patientUser)->get(route('patient.results.show', $result))->assertOk();
    }

    public function test_ac11_ac12_critical_in_app_notify_without_external_before_approve(): void
    {
        Mail::fake();
        [$lab, $order, $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 5,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();

        $alert = CriticalAlert::firstOrFail();
        $this->assertNotNull($alert->notified_at);
        $this->assertSame('sent', $alert->notification_status);

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $doctor->id,
            'type' => Notification::TYPE_CRITICAL_ALERT,
            'priority' => Notification::PRIORITY_CRITICAL,
        ]);

        // External channels must NOT fire for unapproved critical
        $this->assertSame(0, NotificationDelivery::where('channel', 'email')->count());
        $this->assertSame(0, NotificationDelivery::where('channel', 'whatsapp')->count());
        Mail::assertNothingSent();
    }

    public function test_duplicate_approval_event_does_not_duplicate_notifications_or_pdf(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $notifCount = Notification::count();
        $deliveryCount = NotificationDelivery::count();
        $reportCount = GeneratedReport::count();

        app(NotificationOrchestrator::class)->onResultApproved($result->fresh(), $admin);

        $this->assertSame($notifCount, Notification::count());
        $this->assertSame($deliveryCount, NotificationDelivery::count());
        $this->assertSame($reportCount, GeneratedReport::count());
    }

    public function test_whatsapp_failure_logged_without_corrupting_approval(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->app->instance(WhatsAppGateway::class, new class implements WhatsAppGateway
        {
            public function send(string $toPhone, string $message, array $context = []): array
            {
                return [
                    'status' => 'failed',
                    'provider_ref' => null,
                    'error_summary' => 'provider down',
                ];
            }
        });

        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $this->assertSame(Result::STATUS_APPROVED, $result->fresh()->status);
        $wa = NotificationDelivery::where('channel', 'whatsapp')->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_FAILED, $wa->status);
        $this->assertSame('provider down', $wa->error_summary);
        $this->assertDatabaseHas('audit_logs', ['action' => 'whatsapp.failed']);
    }

    public function test_notification_center_mark_read_and_role_isolation(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();

        $doctorNote = Notification::where('user_id', $doctor->id)->where('type', Notification::TYPE_RESULT_APPROVED)->firstOrFail();
        $this->actingAs($doctor)->get(route('notifications.show', $doctorNote))->assertOk();
        $this->assertTrue($doctorNote->fresh()->is_read);

        $this->actingAs($patient)->get(route('notifications.show', $doctorNote))->assertForbidden();

        $this->actingAs($doctor)->post(route('notifications.read_all'))->assertRedirect();
    }

    public function test_patient_structured_requests_workflow(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14,
            'unit' => 'g/dL',
            'action' => 'submit',
        ])->assertRedirect();
        $result = Result::firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $this->actingAs($admin)->post(route('lab.results.approve', $result))->assertRedirect();

        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patient)->post(route('patient.requests.store'), [
            'type' => PatientRequest::TYPE_INTERPRETATION,
            'message' => 'أحتاج تفسير النتيجة',
            'result_id' => $result->id,
        ])->assertRedirect();

        $req = PatientRequest::firstOrFail();
        $this->assertSame(PatientRequest::STATUS_SUBMITTED, $req->status);

        $doctor = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $this->actingAs($doctor)->post(route('doctor.requests.under_review', $req))->assertRedirect();
        $this->assertSame(PatientRequest::STATUS_UNDER_REVIEW, $req->fresh()->status);

        $this->actingAs($doctor)->post(route('doctor.requests.resolve', $req), [
            'resolution_notes' => 'التفسير: ضمن المدى الطبيعي',
        ])->assertRedirect();
        $this->assertSame(PatientRequest::STATUS_RESOLVED, $req->fresh()->status);
    }

    public function test_ac18_archive_connects_pdf_and_approved_result(): void
    {
        Storage::fake('local');
        Mail::fake();

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
        $this->assertSame(OrderStatus::ARCHIVED, $order->status);
        $this->assertNotNull($order->archived_at);
        $this->assertSame(Result::STATUS_APPROVED, $result->fresh()->status);
        $this->assertNotNull(GeneratedReport::where('result_id', $result->id)->first());

        $this->actingAs($admin)->get(route('admin.deliveries.index'))->assertOk();
    }

    public function test_pdf_url_guessing_blocked_and_patient_cannot_see_draft(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$lab, , $item] = $this->orderReadyForEntry();
        $this->actingAs($lab)->post(route('lab.results.save', $item), [
            'value_numeric' => 14.25,
            'unit' => 'g/dL',
            'action' => 'draft',
        ])->assertRedirect();
        $draft = Result::firstOrFail();
        $patient = User::whereHas('roles', fn ($q) => $q->where('code', 'patient'))->firstOrFail();
        $this->actingAs($patient)->get(route('patient.results.show', $draft))
            ->assertOk()
            ->assertDontSee('14.25');

        // Guessing a non-existent report id
        $this->actingAs($patient)->get('/reports/99999/download')->assertNotFound();
    }

    public function test_whatsapp_gateway_is_provider_independent_contract(): void
    {
        $gateway = app(WhatsAppGateway::class);
        $this->assertInstanceOf(WhatsAppGateway::class, $gateway);
        $out = $gateway->send('0500000004', 'hello');
        $this->assertSame('sent', $out['status']);
        $this->assertNotEmpty($out['provider_ref']);
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
