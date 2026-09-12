<?php

namespace Tests\Feature;

use App\Contracts\ResultApprovalContract;
use App\Domain\Workflow\OrderStatus;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_and_root_redirect(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk()->assertSee('LabBI');
    }

    public function test_ac01_admin_can_create_doctor_technician_patient_with_roles(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.doctors.store'), [
            'name' => 'دكتور جديد',
            'email' => 'newdoc@labbi.test',
            'password' => 'password123',
            'specialty' => 'جراحة',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'newdoc@labbi.test']);
        $doctorUser = User::where('email', 'newdoc@labbi.test')->first();
        $this->assertTrue($doctorUser->hasRole('doctor'));

        $this->actingAs($admin)->post(route('admin.lab-staff.store'), [
            'name' => 'فني جديد',
            'email' => 'newlab@labbi.test',
            'password' => 'password123',
            'job_title' => 'فني',
        ])->assertRedirect();
        $this->assertTrue(User::where('email', 'newlab@labbi.test')->first()->hasRole('lab_technician'));

        $this->actingAs($admin)->post(route('admin.patients.store'), [
            'full_name' => 'مريض جديد',
            'phone' => '0551112233',
            'create_login' => 1,
            'email' => 'newpatient@labbi.test',
            'password' => 'password123',
        ])->assertRedirect();

        $patient = Patient::where('full_name', 'مريض جديد')->first();
        $this->assertNotNull($patient);
        $this->assertNotEmpty($patient->medical_file_number);
        $this->assertTrue($patient->user->hasRole('patient'));
    }

    public function test_ac02_patient_cannot_access_another_patient(): void
    {
        $patientUser = $this->userWithRole('patient');
        $otherPatient = Patient::query()->create([
            'medical_file_number' => 'MF-999999',
            'full_name' => 'مريض آخر',
            'phone' => '0599999999',
        ]);

        $this->actingAs($patientUser)
            ->get(route('patient.records.show', $otherPatient))
            ->assertForbidden();
    }

    public function test_ac03_unique_medical_id(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.patients.store'), [
            'full_name' => 'أول',
            'medical_file_number' => 'MF-UNIQUE-1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.patients.store'), [
            'full_name' => 'ثاني',
            'medical_file_number' => 'MF-UNIQUE-1',
        ])->assertSessionHasErrors('medical_file_number');
    }

    public function test_ac04_ac05_doctor_creates_order_visible_to_lab(): void
    {
        $doctorUser = $this->userWithRole('doctor');
        $patient = Patient::firstOrFail();
        $test = LabTest::firstOrFail();

        $response = $this->actingAs($doctorUser)->post(route('doctor.orders.store'), [
            'patient_id' => $patient->id,
            'test_ids' => [$test->id],
            'notes' => 'طلب اختبار',
        ]);
        $response->assertRedirect();

        $order = TestOrder::latest('id')->first();
        $this->assertSame('requested', $order->status);
        $this->assertSame($doctorUser->doctor->id, $order->doctor_id);
        $this->assertSame($patient->id, $order->patient_id);

        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $this->actingAs($lab)
            ->get(route('lab.orders.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_ac06_sample_reception_and_matching(): void
    {
        $this->seedRolesAndPermissions();
        $doctorUser = User::whereHas('roles', fn ($q) => $q->where('code', 'doctor'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();
        $patient = Patient::firstOrFail();
        $test = LabTest::firstOrFail();

        $this->actingAs($doctorUser)->post(route('doctor.orders.store'), [
            'patient_id' => $patient->id,
            'test_ids' => [$test->id],
        ])->assertRedirect();

        $order = TestOrder::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->actingAs($lab)->post(route('lab.samples.receive'), [
            'test_order_id' => $order->id,
            'test_order_item_id' => $item->id,
        ])->assertRedirect(route('lab.samples.index'));

        $sample = Sample::latest('id')->firstOrFail();
        $this->assertSame('received', $sample->status);
        $this->assertSame($order->id, $sample->test_order_id);
        $this->assertSame($patient->id, $sample->patient_id);
        $this->assertSame(OrderStatus::SAMPLE_RECEIVED, $order->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'samples.receive']);
    }

    public function test_ac21_rbac_blocks_unauthorized_access(): void
    {
        $patient = $this->userWithRole('patient');

        $this->actingAs($patient)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($patient)->get(route('lab.samples.reception'))->assertForbidden();
    }

    public function test_rev05_approval_permission_admin_only_not_role_hardcoded(): void
    {
        $this->seedRolesAndPermissions();

        $admin = User::whereHas('roles', fn ($q) => $q->where('code', 'admin'))->firstOrFail();
        $lab = User::whereHas('roles', fn ($q) => $q->where('code', 'lab_technician'))->firstOrFail();

        $service = app(ResultApprovalContract::class);
        $this->assertTrue($service->canApprove($admin->id));
        $this->assertFalse($service->canApprove($lab->id));
        $this->assertTrue($admin->hasPermission('results.approve'));
        $this->assertFalse($lab->hasPermission('results.approve'));

        $this->assertFalse(Role::where('code', 'receptionist')->exists());
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->seedRolesAndPermissions();
        $user = User::where('email', 'doctor@labbi.local')->firstOrFail();
        $user->update(['is_active' => false]);

        $this->post('/login', [
            'email' => 'doctor@labbi.local',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_arabic_shell_on_admin_dashboard(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('LabBI')
            ->assertSee('لوحة تحكم الإدارة');
    }
}
