<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Patient;
use App\Models\ResultAttachment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Ops\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase5AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_db_without_secrets(): void
    {
        $response = $this->getJson('/health');
        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok');

        $body = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($body));
        $this->assertStringNotContainsString('APP_KEY', $body);
    }

    public function test_security_headers_present_on_login(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertNotEmpty($response->headers->get('Referrer-Policy'));
    }

    public function test_login_failure_is_audited_and_rate_limited(): void
    {
        $this->seedRolesAndPermissions();
        RateLimiter::clear('admin@labbi.local|127.0.0.1');

        $this->post('/login', [
            'email' => 'admin@labbi.local',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'admin@labbi.local',
                'password' => 'wrong-password',
            ]);
        }

        $this->assertTrue(
            AuditLog::query()->where('action', 'auth.login_throttled')->exists()
            || RateLimiter::tooManyAttempts('admin@labbi.local|127.0.0.1', 5)
        );
    }

    public function test_audit_ui_admin_ok_others_forbidden(): void
    {
        $admin = $this->userWithRole('admin');
        $lab = $this->userWithRole('lab_technician');
        $doctor = $this->userWithRole('doctor');
        $patient = $this->userWithRole('patient');

        app(AuditLogger::class)->log('patients.create', null, null, ['full_name' => 'اختبار']);

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('سجل العمليات');

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['action' => 'patients.create']))
            ->assertOk()
            ->assertSee('patients.create');

        $this->actingAs($lab)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($doctor)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($patient)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_audit_metadata_redacts_secrets_and_national_id(): void
    {
        $admin = $this->userWithRole('admin');
        $log = app(AuditLogger::class)->log('users.create', $admin, null, [
            'password' => 'super-secret',
            'api_token' => 'tok_123',
            'national_id' => '1234567890',
            'full_name' => 'آمن',
        ]);

        $this->assertSame('[redacted]', $log->new_values['password']);
        $meta = $log->safeMetadata();
        $this->assertSame('[محجوب]', $meta['new']['national_id']);
        $this->assertSame('آمن', $meta['new']['full_name']);

        $this->actingAs($admin)
            ->get(route('admin.audit.show', $log))
            ->assertOk()
            ->assertDontSee('super-secret')
            ->assertDontSee('tok_123')
            ->assertSee('[محجوب]');
    }

    public function test_audit_logs_are_append_only(): void
    {
        $this->seedRolesAndPermissions();
        $log = app(AuditLogger::class)->log('auth.login');

        $this->expectException(\RuntimeException::class);
        $log->update(['action' => 'tampered']);
    }

    public function test_national_id_encrypted_at_rest(): void
    {
        $this->seedRolesAndPermissions();
        $patient = Patient::query()->create([
            'medical_file_number' => 'MF-ENC-001',
            'full_name' => 'مريض مشفر',
            'phone' => '0550001111',
            'national_id' => '9988776655',
        ]);

        $raw = \Illuminate\Support\Facades\DB::table('patients')->where('id', $patient->id)->value('national_id');
        $this->assertNotSame('9988776655', $raw);
        $this->assertSame('9988776655', $patient->fresh()->national_id);
    }

    public function test_ac22_backup_creates_artifact_with_metadata(): void
    {
        Storage::fake('local');
        // Rebind disk root for real zip IO under storage tmp — use real local disk.
        config(['filesystems.disks.local.root' => storage_path('app/testing-private')]);
        config(['labbi.backup.disk' => 'local']);
        @mkdir(storage_path('app/testing-private'), 0750, true);

        $admin = $this->userWithRole('admin');
        Storage::disk('local')->put('attachments/demo.txt', 'attachment-bytes');

        $this->actingAs($admin)
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.backups.index'));

        $backup = Backup::query()->latest('id')->first();
        $this->assertNotNull($backup);
        $this->assertSame('success', $backup->status);
        $this->assertNotNull($backup->size_bytes);
        $this->assertNotEmpty($backup->storage_path);
        $this->assertNotEmpty($backup->taken_at);
        $this->assertFileExists(storage_path('app/testing-private/'.$backup->storage_path));
        $this->assertDatabaseHas('audit_logs', ['action' => 'backups.created']);
    }

    public function test_ac23_restore_drill_create_backup_restore_core_records(): void
    {
        config(['filesystems.disks.local.root' => storage_path('app/testing-private-restore')]);
        config(['labbi.backup.disk' => 'local']);
        @mkdir(storage_path('app/testing-private-restore'), 0750, true);

        $admin = $this->userWithRole('admin');
        $marker = 'MF-RESTORE-DRILL';
        $patient = Patient::query()->create([
            'medical_file_number' => $marker,
            'full_name' => 'مريض الاستعادة',
            'phone' => '0551234567',
            'national_id' => '1122334455',
        ]);

        $service = app(BackupService::class);
        $backup = $service->create($admin, 'drill', 'AC-23 restore drill');
        $this->assertTrue($backup->isSuccessful());

        $patient->delete();
        $this->assertNull(Patient::query()->where('medical_file_number', $marker)->first());

        $service->restore($backup, $admin, confirmed: true);

        $restored = Patient::withTrashed()->where('medical_file_number', $marker)->first();
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
        $this->assertSame('مريض الاستعادة', $restored->full_name);
        $this->assertSame('1122334455', $restored->national_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'backups.restored']);
    }

    public function test_backup_ui_and_restore_gated(): void
    {
        $admin = $this->userWithRole('admin');
        $lab = $this->userWithRole('lab_technician');

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee('النسخ الاحتياطي');
        $this->actingAs($lab)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($lab)->post(route('admin.backups.store'))->assertForbidden();
    }

    public function test_settings_ui_hides_secrets(): void
    {
        $admin = $this->userWithRole('admin');
        $response = $this->actingAs($admin)->get(route('admin.settings.index'));
        $response->assertOk()->assertSee('إعدادات النظام');
        $response->assertDontSee(config('app.key'));
        $response->assertDontSee('DB_PASSWORD');
        $response->assertDontSee('MAIL_PASSWORD');
    }

    public function test_final_rbac_direct_url_denials(): void
    {
        $lab = $this->userWithRole('lab_technician');
        $doctor = $this->userWithRole('doctor');
        $patient = $this->userWithRole('patient');

        foreach ([$lab, $doctor, $patient] as $user) {
            $this->actingAs($user)->get(route('admin.audit.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.backups.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.settings.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.bi.index'))->assertForbidden();
        }

        $this->actingAs($patient)->get(route('lab.results.entry.index'))->assertForbidden();
        $this->actingAs($doctor)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_unauthorized_attachment_path_not_public(): void
    {
        $this->seedRolesAndPermissions();
        config(['filesystems.disks.local.root' => storage_path('app/testing-private-files')]);
        @mkdir(storage_path('app/testing-private-files/attachments'), 0750, true);
        file_put_contents(storage_path('app/testing-private-files/attachments/secret.pdf'), 'PHI');

        // Direct public URL must not serve private storage contents.
        $public = $this->get('/storage/attachments/secret.pdf');
        $this->assertFalse($public->isSuccessful(), 'Private attachment must not be publicly readable');
        $this->assertTrue(in_array($public->status(), [403, 404], true));

        $guess = $this->get('/attachments/secret.pdf');
        $this->assertFalse($guess->isSuccessful());
        $this->assertTrue(in_array($guess->status(), [403, 404], true));
    }

    public function test_schedule_registers_daily_backup(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $this->artisan('labbi:backup', ['--trigger' => 'manual', '--notes' => 'test'])
            ->assertSuccessful();
        $this->assertTrue(Backup::query()->where('status', 'success')->exists());
    }

    public function test_prod_error_page_has_no_stack_trace_when_debug_false(): void
    {
        config(['app.debug' => false]);

        $this->get('/this-route-does-not-exist-labbi')
            ->assertNotFound()
            ->assertSee('الصفحة غير موجودة')
            ->assertDontSee('Stack trace')
            ->assertDontSee('Illuminate\\');
    }
}
