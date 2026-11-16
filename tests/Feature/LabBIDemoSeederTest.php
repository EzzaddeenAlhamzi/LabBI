<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Result;
use App\Models\TestOrder;
use App\Models\User;
use App\Services\Demo\LabBIDemoSeedingService;
use Database\Seeders\LabBIDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class LabBIDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_refuses_production_environment(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('production');

        // Call seeder/service directly (artisan seed prompts for confirmation in production).
        app(LabBIDemoSeedingService::class)->run();
    }

    public function test_small_profile_seeds_valid_workflow_volume_and_preserves_canonical_admin(): void
    {
        config([
            'labbi.demo.profile' => 'small',
            'labbi.demo.seed' => 42,
            'labbi.demo.chunk' => 50,
            'labbi.demo.pdf_cap.small' => 10,
        ]);

        $this->seed(LabBIDemoSeeder::class);

        $admin = User::query()->where('email', 'admin@labbi.local')->firstOrFail();
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));

        $this->assertGreaterThanOrEqual(50, Patient::query()->where('medical_file_number', 'like', 'MF-DEMO42-small-%')->count());
        $this->assertGreaterThanOrEqual(10, User::query()->where('email', 'like', 'demo.doctor.DEMO42-small.%')->count());
        $this->assertGreaterThanOrEqual(5, User::query()->where('email', 'like', 'demo.lab.DEMO42-small.%')->count());
        $this->assertSame(100, TestOrder::query()->where('order_number', 'like', 'ORD-DEMO42-small-%')->count());
        $this->assertGreaterThan(0, Result::query()->where('notes', 'like', '[DEMO]%')->count());

        // Workflow statuses present (valid pipeline mix).
        $statuses = TestOrder::query()
            ->where('order_number', 'like', 'ORD-DEMO42-small-%')
            ->pluck('status')
            ->unique()
            ->sort()
            ->values()
            ->all();
        $this->assertContains('requested', $statuses);
        $this->assertContains('archived', $statuses);
        $this->assertContains('pending_review', $statuses);

        // Canonical demos untouched.
        $this->assertDatabaseHas('users', ['email' => 'doctor@labbi.local']);
        $this->assertDatabaseHas('patients', ['medical_file_number' => 'MF-000001']);
        $this->assertDatabaseHas('tests', ['code' => 'HB']);
    }

    public function test_small_profile_is_idempotent_on_rerun(): void
    {
        config([
            'labbi.demo.profile' => 'small',
            'labbi.demo.seed' => 7,
            'labbi.demo.chunk' => 50,
            'labbi.demo.pdf_cap.small' => 5,
        ]);

        app(LabBIDemoSeedingService::class)->run();
        $ordersFirst = TestOrder::query()->where('order_number', 'like', 'ORD-DEMO7-small-%')->count();
        $patientsFirst = Patient::query()->where('medical_file_number', 'like', 'MF-DEMO7-small-%')->count();

        app(LabBIDemoSeedingService::class)->run();
        $ordersSecond = TestOrder::query()->where('order_number', 'like', 'ORD-DEMO7-small-%')->count();
        $patientsSecond = Patient::query()->where('medical_file_number', 'like', 'MF-DEMO7-small-%')->count();

        $this->assertSame($ordersFirst, $ordersSecond);
        $this->assertSame($patientsFirst, $patientsSecond);
        $this->assertSame(100, $ordersSecond);
    }

    public function test_database_seeder_does_not_auto_run_volume_demo(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(0, TestOrder::query()->where('order_number', 'like', 'ORD-DEMO%')->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@labbi.local']);
    }
}
