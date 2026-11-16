<?php

namespace App\Services\Demo;

use App\Domain\Critical\CriticalValueEngine;
use App\Domain\Critical\ResultClassification;
use App\Domain\Workflow\OrderStatus;
use App\Models\CriticalAlert;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\GeneratedReport;
use App\Models\LaboratoryStaff;
use App\Models\LabTest;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Patient;
use App\Models\PatientRequest;
use App\Models\Result;
use App\Models\ResultReview;
use App\Models\Role;
use App\Models\Sample;
use App\Models\TestOrder;
use App\Models\TestOrderItem;
use App\Models\TestReferenceRange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DEVELOPMENT/STAGING demo volume seeder.
 * Synthetic data only — preserves canonical admins/roles/passwords.
 * Catalog ranges are demo-only until CAT-04 medical sign-off.
 */
class LabBIDemoSeedingService
{
    /** @var array<string, int|float> */
    private array $targets;

    private string $profile;

    private int $seed;

    private int $chunk;

    private string $ns;

    /** @var array<string, int> */
    private array $counts = [
        'patients' => 0,
        'doctors' => 0,
        'lab_staff' => 0,
        'tests' => 0,
        'orders' => 0,
        'items' => 0,
        'samples' => 0,
        'results' => 0,
        'reviews' => 0,
        'alerts' => 0,
        'notifications' => 0,
        'deliveries' => 0,
        'pdfs' => 0,
        'requests' => 0,
    ];

    /** @var list<string> */
    private array $progress = [];

    public function __construct(
        private readonly CriticalValueEngine $engine,
    ) {}

    /**
     * @return array{profile: string, seed: int, counts: array<string, int>, progress: list<string>}
     */
    public function run(?callable $log = null): array
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LabBIDemoSeeder refuses APP_ENV=production.');
        }

        Mail::fake();

        $this->profile = strtolower((string) config('labbi.demo.profile', 'small'));
        $profiles = config('labbi.demo.profiles', []);
        if (! isset($profiles[$this->profile])) {
            throw new RuntimeException("Unknown LABBI_DEMO_PROFILE [{$this->profile}]. Use small|medium|large.");
        }

        $this->targets = $profiles[$this->profile];
        $this->seed = (int) config('labbi.demo.seed', 42);
        $this->chunk = max(50, (int) config('labbi.demo.chunk', 250));
        $this->ns = 'DEMO'.$this->seed.'-'.$this->profile;

        mt_srand($this->seed);
        fake()->seed($this->seed);

        $log ??= fn (string $m) => null;
        $this->log($log, "Profile={$this->profile} seed={$this->seed} ns={$this->ns}");

        // Ensure baseline RBAC + canonical demo users/catalog exist without bulk data.
        if (! User::query()->where('email', 'admin@labbi.local')->exists()) {
            $this->log($log, 'Bootstrapping DatabaseSeeder (RBAC + canonical demos)…');
            app(\Database\Seeders\DatabaseSeeder::class)->run();
        } elseif (! LabTest::query()->where('code', 'HB')->exists()) {
            app(\Database\Seeders\DemoDataSeeder::class)->run();
        }

        $this->purgePreviousSynthetic($log);
        $this->seedPeople($log);
        $this->seedCatalog($log);
        $this->seedOrdersPipeline($log);
        $this->seedPatientRequests($log);

        $this->log($log, 'Demo seeding complete.');

        return [
            'profile' => $this->profile,
            'seed' => $this->seed,
            'counts' => $this->counts,
            'progress' => $this->progress,
        ];
    }

    private function log(callable $log, string $message): void
    {
        $this->progress[] = $message;
        $log($message);
    }

    private function purgePreviousSynthetic(callable $log): void
    {
        $this->log($log, 'Purging prior synthetic demo rows (namespace '.$this->ns.')…');

        DB::transaction(function () {
            // Orders cascade to items/samples/results/alerts/reviews via FKs when deleted.
            TestOrder::query()->where('order_number', 'like', 'ORD-'.$this->ns.'-%')->delete();

            PatientRequest::query()
                ->where('message', 'like', '[DEMO '.$this->ns.']%')
                ->delete();

            Notification::query()
                ->where('dedupe_key', 'like', 'demo:'.$this->ns.':%')
                ->delete();

            GeneratedReport::query()
                ->where('idempotency_key', 'like', 'demo_pdf:'.$this->ns.':%')
                ->delete();

            Patient::withTrashed()
                ->where('medical_file_number', 'like', 'MF-'.$this->ns.'-%')
                ->forceDelete();

            Doctor::query()->where('license_number', 'like', 'DOC-'.$this->ns.'-%')->delete();
            LaboratoryStaff::query()
                ->whereHas('user', fn ($q) => $q->where('email', 'like', 'demo.lab.'.$this->ns.'.%@labbi.local'))
                ->delete();

            User::withTrashed()
                ->where('email', 'like', 'demo.%.'.$this->ns.'.%@labbi.local')
                ->forceDelete();

            LabTest::query()->where('code', 'like', 'D'.$this->seed.'T%')->delete();
        });
    }

    private function seedPeople(callable $log): void
    {
        $this->log($log, 'Seeding people…');

        $roles = Role::query()->whereIn('code', ['doctor', 'lab_technician', 'patient'])->get()->keyBy('code');
        $deptIds = Department::query()->pluck('id')->all();
        if ($deptIds === []) {
            throw new RuntimeException('No departments — run DemoDataSeeder first.');
        }

        $password = Hash::make('password');
        $doctorsNeeded = (int) $this->targets['doctors'];
        $labNeeded = (int) $this->targets['lab_staff'];
        $patientsNeeded = (int) $this->targets['patients'];

        // Doctors
        for ($i = 1; $i <= $doctorsNeeded; $i++) {
            $email = sprintf('demo.doctor.%s.%04d@labbi.local', $this->ns, $i);
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Demo Doctor '.$i,
                    'password' => $password,
                    'is_active' => true,
                    'phone' => sprintf('05%08d', 10000000 + $i),
                ]
            );
            $user->roles()->syncWithoutDetaching([$roles['doctor']->id]);
            Doctor::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number' => sprintf('DOC-%s-%04d', $this->ns, $i),
                    'specialty' => 'طب عام (تجريبي)',
                    'department_id' => $deptIds[($i - 1) % count($deptIds)],
                ]
            );
            $this->counts['doctors']++;
        }

        // Lab staff
        for ($i = 1; $i <= $labNeeded; $i++) {
            $email = sprintf('demo.lab.%s.%04d@labbi.local', $this->ns, $i);
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Demo Lab '.$i,
                    'password' => $password,
                    'is_active' => true,
                    'phone' => sprintf('05%08d', 20000000 + $i),
                ]
            );
            $user->roles()->syncWithoutDetaching([$roles['lab_technician']->id]);
            LaboratoryStaff::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $deptIds[($i - 1) % count($deptIds)],
                    'job_title' => 'فني مختبر (تجريبي)',
                ]
            );
            $this->counts['lab_staff']++;
        }

        $doctorIds = Doctor::query()
            ->where('license_number', 'like', 'DOC-'.$this->ns.'-%')
            ->pluck('id')
            ->all();
        // Include canonical doctor so dashboards stay linked.
        $canonicalDoctor = Doctor::query()->where('license_number', 'DOC-1001')->value('id');
        if ($canonicalDoctor) {
            $doctorIds[] = $canonicalDoctor;
            $doctorIds = array_values(array_unique($doctorIds));
        }

        for ($i = 1; $i <= $patientsNeeded; $i++) {
            $email = sprintf('demo.patient.%s.%05d@labbi.local', $this->ns, $i);
            $hasPortal = ($i % 5) !== 0; // ~80% portal users
            $userId = null;
            if ($hasPortal) {
                $user = User::query()->firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => 'Demo Patient '.$i,
                        'password' => $password,
                        'is_active' => true,
                        'phone' => sprintf('05%08d', 30000000 + ($i % 10000000)),
                    ]
                );
                $user->roles()->syncWithoutDetaching([$roles['patient']->id]);
                $userId = $user->id;
            }

            Patient::query()->firstOrCreate(
                ['medical_file_number' => sprintf('MF-%s-%05d', $this->ns, $i)],
                [
                    'user_id' => $userId,
                    'full_name' => 'مريض تجريبي '.$i,
                    'date_of_birth' => sprintf('%04d-%02d-%02d', 1960 + ($i % 40), 1 + ($i % 12), 1 + ($i % 28)),
                    'sex' => ($i % 2 === 0) ? 'male' : 'female',
                    'phone' => sprintf('05%08d', 30000000 + ($i % 10000000)),
                    'national_id' => null, // no real PHI
                    'primary_doctor_id' => $doctorIds[($i - 1) % count($doctorIds)],
                ]
            );
            $this->counts['patients']++;

            if ($i % $this->chunk === 0) {
                $this->log($log, "  patients {$i}/{$patientsNeeded}");
            }
        }

        $this->log($log, "People done: doctors={$this->counts['doctors']} lab={$this->counts['lab_staff']} patients={$this->counts['patients']}");
    }

    private function seedCatalog(callable $log): void
    {
        $this->log($log, 'Ensuring catalog (≥ target tests, demo ranges)…');

        $deptH = Department::query()->where('code', 'HEMATOLOGY')->first()
            ?? Department::query()->firstOrFail();
        $deptC = Department::query()->where('code', 'CHEMISTRY')->first() ?? $deptH;

        $catalog = $this->demoCatalogDefinitions($deptH->id, $deptC->id);
        $needed = max((int) $this->targets['tests'], 2);

        $created = 0;
        foreach (array_slice($catalog, 0, $needed) as $def) {
            $test = LabTest::query()->firstOrCreate(
                ['code' => $def['code']],
                [
                    'name_ar' => $def['name_ar'],
                    'name_en' => $def['name_en'],
                    'unit' => $def['unit'],
                    'department_id' => $def['department_id'],
                    'is_active' => true,
                ]
            );

            TestReferenceRange::query()->firstOrCreate(
                ['test_id' => $test->id, 'is_active' => true],
                [
                    'normal_low' => $def['normal_low'],
                    'normal_high' => $def['normal_high'],
                    'critical_low' => $def['critical_low'],
                    'critical_high' => $def['critical_high'],
                    // DEMO / TEST-ONLY until CAT-04 — not clinical authority.
                ]
            );
            $created++;
        }

        $this->counts['tests'] = LabTest::query()->where('is_active', true)->count();
        $this->log($log, "Catalog ready: active_tests={$this->counts['tests']} (ensured {$created} defs)");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoCatalogDefinitions(int $hemeId, int $chemId): array
    {
        // HB / GLU already exist from DemoDataSeeder — reuse codes.
        $defs = [
            ['code' => 'HB', 'name_ar' => 'الهيموغلوبين', 'name_en' => 'Hemoglobin', 'unit' => 'g/dL', 'department_id' => $hemeId, 'normal_low' => 12, 'normal_high' => 16, 'critical_low' => 7, 'critical_high' => 20],
            ['code' => 'GLU', 'name_ar' => 'السكر في الدم', 'name_en' => 'Glucose', 'unit' => 'mg/dL', 'department_id' => $chemId, 'normal_low' => 70, 'normal_high' => 99, 'critical_low' => 40, 'critical_high' => 400],
        ];

        $extras = [
            ['WBC', 'كريات الدم البيضاء', 'WBC', '10^3/uL', $hemeId, 4.5, 11.0, 2.0, 30.0],
            ['PLT', 'الصفائح الدموية', 'Platelets', '10^3/uL', $hemeId, 150, 400, 50, 1000],
            ['HCT', 'الهيماتوكريت', 'Hematocrit', '%', $hemeId, 36, 48, 20, 60],
            ['RBC', 'كريات الدم الحمراء', 'RBC', '10^6/uL', $hemeId, 4.2, 5.9, 2.5, 7.5],
            ['UREA', 'اليوريا', 'Urea', 'mg/dL', $chemId, 15, 40, 5, 100],
            ['CREA', 'الكرياتينين', 'Creatinine', 'mg/dL', $chemId, 0.6, 1.3, 0.2, 5.0],
            ['NA', 'الصوديوم', 'Sodium', 'mmol/L', $chemId, 135, 145, 120, 160],
            ['K', 'البوتاسيوم', 'Potassium', 'mmol/L', $chemId, 3.5, 5.1, 2.5, 6.5],
            ['ALT', 'إنزيم ALT', 'ALT', 'U/L', $chemId, 7, 56, 5, 500],
            ['AST', 'إنزيم AST', 'AST', 'U/L', $chemId, 10, 40, 5, 500],
            ['ALP', 'إنزيم ALP', 'ALP', 'U/L', $chemId, 44, 147, 20, 500],
            ['BILI', 'البيليروبين', 'Bilirubin', 'mg/dL', $chemId, 0.1, 1.2, 0.05, 15],
            ['CHOL', 'الكوليسترول', 'Cholesterol', 'mg/dL', $chemId, 125, 200, 80, 400],
            ['TG', 'الدهون الثلاثية', 'Triglycerides', 'mg/dL', $chemId, 50, 150, 20, 1000],
            ['HDL', 'HDL', 'HDL', 'mg/dL', $chemId, 40, 60, 15, 100],
            ['LDL', 'LDL', 'LDL', 'mg/dL', $chemId, 50, 130, 20, 300],
            ['TSH', 'هرمون TSH', 'TSH', 'mIU/L', $chemId, 0.4, 4.0, 0.05, 20],
            ['CRP', 'بروتين CRP', 'CRP', 'mg/L', $chemId, 0, 5, 0, 200],
        ];

        foreach ($extras as $i => $row) {
            $defs[] = [
                'code' => 'D'.$this->seed.'T'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'name_ar' => $row[1].' (تجريبي)',
                'name_en' => $row[2].' (demo)',
                'unit' => $row[3],
                'department_id' => $row[4],
                'normal_low' => $row[5],
                'normal_high' => $row[6],
                'critical_low' => $row[7],
                'critical_high' => $row[8],
            ];
        }

        return $defs;
    }

    private function seedOrdersPipeline(callable $log): void
    {
        $orderTarget = (int) $this->targets['orders'];
        $this->log($log, "Seeding ~{$orderTarget} orders (chunk={$this->chunk})…");

        $patients = Patient::query()
            ->where('medical_file_number', 'like', 'MF-'.$this->ns.'-%')
            ->pluck('id')
            ->all();
        if ($patients === []) {
            throw new RuntimeException('No demo patients to attach orders.');
        }

        $doctors = Doctor::query()
            ->where(function ($q) {
                $q->where('license_number', 'like', 'DOC-'.$this->ns.'-%')
                    ->orWhere('license_number', 'DOC-1001');
            })
            ->with('user')
            ->get();
        $labUsers = User::query()
            ->where(function ($q) {
                $q->where('email', 'like', 'demo.lab.'.$this->ns.'.%@labbi.local')
                    ->orWhere('email', 'lab@labbi.local');
            })
            ->pluck('id')
            ->all();
        $admin = User::query()->where('email', 'admin@labbi.local')->firstOrFail();

        $tests = LabTest::query()->where('is_active', true)->with(['referenceRanges' => fn ($q) => $q->where('is_active', true)->orderByDesc('id')])->get();
        if ($tests->isEmpty()) {
            throw new RuntimeException('No active tests.');
        }

        $stages = $this->stageWeights();
        $pdfCap = (int) (config('labbi.demo.pdf_cap.'.$this->profile) ?? 25);
        $pdfsMade = 0;

        $created = 0;

        for ($start = 1; $start <= $orderTarget; $start += $this->chunk) {
            $end = min($orderTarget, $start + $this->chunk - 1);
            DB::transaction(function () use (
                $start, $end, $patients, $doctors, $labUsers, $admin, $tests, $stages, &$created, &$pdfsMade, $pdfCap, $log
            ) {
                for ($i = $start; $i <= $end; $i++) {
                    $stage = $this->pickStage($stages, $i);
                    $patientId = $patients[($i - 1) % count($patients)];
                    $doctor = $doctors[($i - 1) % $doctors->count()];
                    $labUserId = $labUsers[($i - 1) % count($labUsers)];
                    $orderedAt = now()->subDays(90)->addMinutes($i * 7);

                    // Target ~avg_items 1.75: 50%×1 + 25%×2 + 25%×3
                    $itemCount = match ($i % 4) {
                        0, 1 => 1,
                        2 => 2,
                        default => 3,
                    };

                    $order = TestOrder::query()->create([
                        'order_number' => sprintf('ORD-%s-%06d', $this->ns, $i),
                        'patient_id' => $patientId,
                        'doctor_id' => $doctor->id,
                        'department_id' => $doctor->department_id,
                        'status' => OrderStatus::REQUESTED,
                        'ordered_at' => $orderedAt,
                        'created_by' => $doctor->user_id ?? $admin->id,
                        'notes' => '[DEMO] synthetic order — not clinical',
                    ]);
                    $this->counts['orders']++;

                    $items = [];
                    for ($j = 0; $j < $itemCount; $j++) {
                        $test = $tests[($i + $j) % $tests->count()];
                        $item = TestOrderItem::query()->create([
                            'test_order_id' => $order->id,
                            'test_id' => $test->id,
                            'status' => 'requested',
                        ]);
                        $items[] = [$item, $test];
                        $this->counts['items']++;
                    }

                    $this->advanceOrder($order, $items, $stage, $orderedAt, $labUserId, $admin, $doctor, $i, $pdfsMade, $pdfCap);
                    $created++;
                }
            });

            $this->log($log, "  orders {$end}/{$orderTarget} (items={$this->counts['items']} results={$this->counts['results']})");
        }
    }

    /**
     * @return array<string, float>
     */
    private function stageWeights(): array
    {
        return [
            OrderStatus::REQUESTED => 0.08,
            OrderStatus::SAMPLE_RECEIVED => 0.08,
            OrderStatus::IN_ANALYSIS => 0.10,
            OrderStatus::PENDING_REVIEW => 0.12,
            OrderStatus::READY => 0.08,
            OrderStatus::ARCHIVED => 0.38,
            OrderStatus::DEFERRED => 0.05,
            OrderStatus::REJECTED => 0.05,
            'returned_mid' => 0.06, // pending_review with returned item path → in_analysis-like returned result
        ];
    }

    /** @param array<string, float> $weights */
    private function pickStage(array $weights, int $i): string
    {
        $keys = array_keys($weights);
        // Deterministic distribution without floating drift.
        $bucket = $i % 100;
        $acc = 0;
        foreach ($weights as $stage => $w) {
            $acc += (int) round($w * 100);
            if ($bucket < $acc) {
                return $stage;
            }
        }

        return $keys[array_key_last($keys)];
    }

    /**
     * @param  list<array{0: TestOrderItem, 1: LabTest}>  $items
     */
    private function advanceOrder(
        TestOrder $order,
        array $items,
        string $stage,
        \Carbon\CarbonInterface $orderedAt,
        int $labUserId,
        User $admin,
        Doctor $doctor,
        int $orderIndex,
        int &$pdfsMade,
        int $pdfCap,
    ): void {
        if ($stage === OrderStatus::REQUESTED) {
            return;
        }

        if ($stage === OrderStatus::DEFERRED) {
            $order->update(['status' => OrderStatus::DEFERRED, 'notes' => $order->notes."\n[DEMO] deferred"]);

            return;
        }

        if ($stage === OrderStatus::REJECTED) {
            $order->update(['status' => OrderStatus::REJECTED, 'notes' => $order->notes."\n[DEMO] rejected"]);

            return;
        }

        $receivedAt = $orderedAt->copy()->addHours(2);
        foreach ($items as $itemIdx => [$item, $test]) {
            Sample::query()->create([
                'sample_number' => sprintf('SMP-%s-%06d-%02d', $this->ns, $orderIndex, $itemIdx + 1),
                'test_order_id' => $order->id,
                'patient_id' => $order->patient_id,
                'test_order_item_id' => $item->id,
                'test_id' => $test->id,
                'received_at' => $receivedAt,
                'received_by' => $labUserId,
                'status' => 'received',
            ]);
            $this->counts['samples']++;
        }

        $order->update(['status' => OrderStatus::SAMPLE_RECEIVED]);
        if ($stage === OrderStatus::SAMPLE_RECEIVED) {
            return;
        }

        $analysisAt = $receivedAt->copy()->addHours(3);
        $order->update([
            'status' => OrderStatus::IN_ANALYSIS,
            'analysis_started_at' => $analysisAt,
        ]);

        if ($stage === OrderStatus::IN_ANALYSIS) {
            // Drafts for first item only.
            [$item, $test] = $items[0];
            $this->createResult($item, $test, $order, $labUserId, $analysisAt->copy()->addHour(), Result::STATUS_DRAFT, 'normal');
            $item->update(['status' => Result::STATUS_DRAFT]);

            return;
        }

        if ($stage === 'returned_mid') {
            foreach ($items as $idx => [$item, $test]) {
                $entered = $analysisAt->copy()->addHours(1 + $idx);
                $result = $this->createResult($item, $test, $order, $labUserId, $entered, Result::STATUS_RETURNED, 'abnormal');
                $item->update(['status' => Result::STATUS_RETURNED]);
                ResultReview::query()->create([
                    'result_id' => $result->id,
                    'reviewer_id' => $admin->id,
                    'action' => 'return',
                    'comment' => '[DEMO] returned for correction',
                    'reviewed_at' => $entered->copy()->addHour(),
                ]);
                $this->counts['reviews']++;
            }
            $order->update(['status' => OrderStatus::IN_ANALYSIS]);

            return;
        }

        // Submit all items
        $results = [];
        foreach ($items as $idx => [$item, $test]) {
            $band = $this->valueBandForIndex($orderIndex + $idx);
            $entered = $analysisAt->copy()->addHours(1 + $idx);
            $submitted = $entered->copy()->addMinutes(30);
            $result = $this->createResult($item, $test, $order, $labUserId, $entered, Result::STATUS_PENDING_REVIEW, $band, $submitted);
            $item->update(['status' => Result::STATUS_PENDING_REVIEW]);
            $results[] = [$result, $band];
        }
        $order->update(['status' => OrderStatus::PENDING_REVIEW]);

        if ($stage === OrderStatus::PENDING_REVIEW) {
            return;
        }

        // Approve all
        foreach ($results as $idx => [$result, $band]) {
            $approvedAt = $analysisAt->copy()->addHours(6 + $idx);
            $result->update([
                'status' => Result::STATUS_APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => $approvedAt,
            ]);
            $result->orderItem?->update(['status' => Result::STATUS_APPROVED]);
            ResultReview::query()->create([
                'result_id' => $result->id,
                'reviewer_id' => $admin->id,
                'action' => 'approve',
                'comment' => '[DEMO] approved',
                'reviewed_at' => $approvedAt,
            ]);
            $this->counts['reviews']++;

            if ($band === 'critical') {
                $this->ensureCriticalAlert($result, $order, $doctor, $approvedAt->copy()->subHours(2));
            }

            $this->seedPostApprovalArtifacts($result, $order, $doctor, $approvedAt, $pdfsMade, $pdfCap);
        }

        $readyAt = $analysisAt->copy()->addHours(8);
        if ($stage === OrderStatus::READY) {
            $order->update(['status' => OrderStatus::READY]);

            return;
        }

        // ARCHIVED happy path (completed → archived chronologically)
        $order->update([
            'status' => OrderStatus::ARCHIVED,
            'completed_at' => $readyAt,
            'archived_at' => $readyAt->copy()->addHour(),
        ]);
    }

    private function valueBandForIndex(int $n): string
    {
        return match ($n % 10) {
            0 => 'critical',
            1, 2 => 'abnormal',
            default => 'normal',
        };
    }

    private function createResult(
        TestOrderItem $item,
        LabTest $test,
        TestOrder $order,
        int $labUserId,
        \Carbon\CarbonInterface $enteredAt,
        string $status,
        string $band,
        ?\Carbon\CarbonInterface $submittedAt = null,
    ): Result {
        $range = $test->referenceRanges->first()
            ?? $test->referenceRanges()->where('is_active', true)->orderByDesc('id')->first();

        $value = $this->syntheticValue($range, $band);
        $outcome = $this->engine->classify($value, $range, $test->unit, $test->unit);

        // Ensure band intent matches engine when range exists.
        if ($outcome->classification === ResultClassification::UNKNOWN && $range) {
            $value = (float) $range->normal_low + ((float) $range->normal_high - (float) $range->normal_low) / 2;
            $outcome = $this->engine->classify($value, $range, $test->unit, $test->unit);
        }

        $result = Result::query()->create([
            'test_order_item_id' => $item->id,
            'test_order_id' => $order->id,
            'patient_id' => $order->patient_id,
            'test_id' => $test->id,
            'value_numeric' => $value,
            'unit' => $test->unit,
            'classification' => $outcome->classification,
            'status' => $status,
            'notes' => '[DEMO] synthetic result — CAT-04 ranges demo-only',
            'entered_by' => $labUserId,
            'entered_at' => $enteredAt,
            'submitted_at' => $submittedAt,
            'updated_by' => $labUserId,
            'reference_range_id' => $outcome->referenceRangeId,
            'range_snapshot' => $outcome->thresholdSnapshot,
        ]);
        $this->counts['results']++;

        if ($status === Result::STATUS_PENDING_REVIEW && $outcome->isCritical) {
            $this->ensureCriticalAlert($result, $order, $order->doctor()->first(), $enteredAt);
        }

        return $result;
    }

    private function syntheticValue(?TestReferenceRange $range, string $band): float
    {
        if (! $range) {
            return 10.0;
        }

        $nLow = (float) ($range->normal_low ?? 10);
        $nHigh = (float) ($range->normal_high ?? 20);
        $cLow = (float) ($range->critical_low ?? ($nLow - 5));
        $cHigh = (float) ($range->critical_high ?? ($nHigh + 5));

        return match ($band) {
            'critical' => $cLow - abs($nLow - $cLow) * 0.1 - 0.01,
            'abnormal' => $nHigh + max(0.01, ($cHigh - $nHigh) * 0.3),
            default => $nLow + ($nHigh - $nLow) * 0.5,
        };
    }

    private function ensureCriticalAlert(
        Result $result,
        TestOrder $order,
        ?Doctor $doctor,
        \Carbon\CarbonInterface $discoveredAt,
    ): void {
        if (CriticalAlert::query()->where('result_id', $result->id)->exists()) {
            return;
        }

        $alert = CriticalAlert::query()->create([
            'result_id' => $result->id,
            'patient_id' => $result->patient_id,
            'test_order_id' => $result->test_order_id,
            'test_id' => $result->test_id,
            'doctor_id' => $doctor?->id ?? $order->doctor_id,
            'detected_value' => $result->value_numeric,
            'unit' => $result->unit,
            'threshold_snapshot' => $result->range_snapshot,
            'discovered_at' => $discoveredAt,
            'notified_at' => $discoveredAt->copy()->addMinutes(5),
            'notification_status' => 'sent',
            'status' => CriticalAlert::STATUS_OPEN,
        ]);
        $this->counts['alerts']++;

        $doctorUserId = $doctor?->user_id;
        if ($doctorUserId) {
            $this->upsertNotification([
                'type' => Notification::TYPE_CRITICAL_ALERT,
                'user_id' => $doctorUserId,
                'patient_id' => $result->patient_id,
                'test_order_id' => $result->test_order_id,
                'result_id' => $result->id,
                'test_id' => $result->test_id,
                'title' => 'تنبيه قيمة حرجة (تجريبي)',
                'message' => '[DEMO] critical alert for synthetic result '.$result->id,
                'priority' => Notification::PRIORITY_CRITICAL,
                'link_url' => '/doctor/critical',
                'dedupe_key' => 'demo:'.$this->ns.':critical:'.$result->id,
            ], NotificationDelivery::CHANNEL_IN_APP, (string) $doctorUserId, 'demo:'.$this->ns.':critical:'.$result->id.':in_app');
        }
    }

    private function seedPostApprovalArtifacts(
        Result $result,
        TestOrder $order,
        Doctor $doctor,
        \Carbon\CarbonInterface $at,
        int &$pdfsMade,
        int $pdfCap,
    ): void {
        $doctorUserId = $doctor->user_id;
        if ($doctorUserId) {
            $this->upsertNotification([
                'type' => Notification::TYPE_RESULT_APPROVED,
                'user_id' => $doctorUserId,
                'patient_id' => $result->patient_id,
                'test_order_id' => $order->id,
                'result_id' => $result->id,
                'test_id' => $result->test_id,
                'title' => 'نتيجة معتمدة (تجريبي)',
                'message' => '[DEMO] result approved '.$result->id,
                'priority' => Notification::PRIORITY_NORMAL,
                'link_url' => '/doctor/results',
                'dedupe_key' => 'demo:'.$this->ns.':approved:'.$result->id.':doctor',
            ], NotificationDelivery::CHANNEL_IN_APP, (string) $doctorUserId, 'demo:'.$this->ns.':approved:'.$result->id.':doctor_in_app');

            $this->upsertNotification([
                'type' => Notification::TYPE_RESULT_APPROVED,
                'user_id' => $doctorUserId,
                'patient_id' => $result->patient_id,
                'test_order_id' => $order->id,
                'result_id' => $result->id,
                'test_id' => $result->test_id,
                'title' => 'بريد نتيجة (تجريبي)',
                'message' => '[DEMO] email delivery stub — Mail::fake',
                'priority' => Notification::PRIORITY_NORMAL,
                'link_url' => '/doctor/results',
                'dedupe_key' => 'demo:'.$this->ns.':approved:'.$result->id.':doctor_email',
            ], NotificationDelivery::CHANNEL_EMAIL, 'doctor-demo@labbi.local', 'demo:'.$this->ns.':approved:'.$result->id.':doctor_email');
        }

        $patient = Patient::query()->find($result->patient_id);
        if ($patient?->user_id) {
            $this->upsertNotification([
                'type' => Notification::TYPE_RESULT_READY,
                'user_id' => $patient->user_id,
                'patient_id' => $patient->id,
                'test_order_id' => $order->id,
                'result_id' => $result->id,
                'test_id' => $result->test_id,
                'title' => 'نتيجتك جاهزة (تجريبي)',
                'message' => '[DEMO] patient result ready',
                'priority' => Notification::PRIORITY_NORMAL,
                'link_url' => '/patient/tests',
                'dedupe_key' => 'demo:'.$this->ns.':ready:'.$result->id.':patient',
            ], NotificationDelivery::CHANNEL_IN_APP, (string) $patient->user_id, 'demo:'.$this->ns.':ready:'.$result->id.':patient_in_app');

            if ($patient->phone) {
                $this->upsertNotification([
                    'type' => Notification::TYPE_RESULT_READY,
                    'user_id' => $patient->user_id,
                    'patient_id' => $patient->id,
                    'test_order_id' => $order->id,
                    'result_id' => $result->id,
                    'test_id' => $result->test_id,
                    'title' => 'واتساب (تجريبي)',
                    'message' => '[DEMO] WhatsApp mock delivery',
                    'priority' => Notification::PRIORITY_NORMAL,
                    'link_url' => '/patient/tests',
                    'dedupe_key' => 'demo:'.$this->ns.':ready:'.$result->id.':patient_wa',
                ], NotificationDelivery::CHANNEL_WHATSAPP, $patient->phone, 'demo:'.$this->ns.':ready:'.$result->id.':patient_wa');
            }
        }

        if ($pdfsMade < $pdfCap) {
            $key = 'demo_pdf:'.$this->ns.':'.$result->id.':v1';
            if (! GeneratedReport::query()->where('idempotency_key', $key)->exists()) {
                $storageKey = 'reports/demo/'.$this->ns.'/'.Str::uuid()->toString().'.pdf';
                // Minimal valid PDF bytes (real path on local disk).
                Storage::disk('local')->put($storageKey, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
                GeneratedReport::query()->create([
                    'result_id' => $result->id,
                    'test_order_id' => $order->id,
                    'patient_id' => $result->patient_id,
                    'report_type' => GeneratedReport::TYPE_RESULT_PDF,
                    'storage_disk' => 'local',
                    'storage_key' => $storageKey,
                    'version' => 1,
                    'generated_by' => $order->created_by,
                    'generated_at' => $at,
                    'idempotency_key' => $key,
                ]);
                $pdfsMade++;
                $this->counts['pdfs']++;
            }
        }
    }

    /** @param array<string, mixed> $attrs */
    private function upsertNotification(array $attrs, string $channel, string $recipient, string $deliveryKey): void
    {
        $notification = Notification::query()->firstOrCreate(
            ['dedupe_key' => $attrs['dedupe_key']],
            $attrs + ['is_read' => false]
        );
        if ($notification->wasRecentlyCreated) {
            $this->counts['notifications']++;
        }

        $delivery = NotificationDelivery::query()->firstOrCreate(
            ['idempotency_key' => $deliveryKey],
            [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => NotificationDelivery::STATUS_SENT,
                'attempts' => 1,
                'provider_ref' => $channel === NotificationDelivery::CHANNEL_WHATSAPP ? 'mock-wa-demo' : 'demo',
                'sent_at' => now(),
            ]
        );
        if ($delivery->wasRecentlyCreated) {
            $this->counts['deliveries']++;
        }
    }

    private function seedPatientRequests(callable $log): void
    {
        $this->log($log, 'Seeding patient requests…');

        $approved = Result::query()
            ->where('status', Result::STATUS_APPROVED)
            ->where('notes', 'like', '[DEMO]%')
            ->with(['patient.user', 'order.doctor'])
            ->orderBy('id')
            ->limit($this->profile === 'small' ? 20 : ($this->profile === 'medium' ? 80 : 200))
            ->get();

        $adminId = User::query()->where('email', 'admin@labbi.local')->value('id');

        foreach ($approved as $idx => $result) {
            $patient = $result->patient;
            if (! $patient?->user_id) {
                continue;
            }

            $status = match ($idx % 4) {
                0 => PatientRequest::STATUS_SUBMITTED,
                1 => PatientRequest::STATUS_UNDER_REVIEW,
                2 => PatientRequest::STATUS_RESOLVED,
                default => PatientRequest::STATUS_REJECTED,
            };

            $req = PatientRequest::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $result->order->doctor_id,
                'result_id' => $result->id,
                'test_order_id' => $result->test_order_id,
                'type' => ($idx % 2 === 0) ? PatientRequest::TYPE_INTERPRETATION : PatientRequest::TYPE_RETEST,
                'message' => '[DEMO '.$this->ns.'] synthetic patient request #'.($idx + 1),
                'status' => $status,
                'resolution_notes' => in_array($status, [PatientRequest::STATUS_RESOLVED, PatientRequest::STATUS_REJECTED], true)
                    ? '[DEMO] resolution notes'
                    : null,
                'resolved_at' => in_array($status, [PatientRequest::STATUS_RESOLVED, PatientRequest::STATUS_REJECTED], true)
                    ? now()->subDays(1)
                    : null,
                'resolved_by' => in_array($status, [PatientRequest::STATUS_RESOLVED, PatientRequest::STATUS_REJECTED], true)
                    ? $adminId
                    : null,
            ]);
            $this->counts['requests']++;

            $doctorUserId = $result->order->doctor?->user_id
                ?? Doctor::query()->whereKey($result->order->doctor_id)->value('user_id');
            if ($doctorUserId) {
                $this->upsertNotification([
                    'type' => Notification::TYPE_REQUEST_UPDATE,
                    'user_id' => $doctorUserId,
                    'patient_id' => $patient->id,
                    'test_order_id' => $result->test_order_id,
                    'result_id' => $result->id,
                    'title' => 'طلب مريض (تجريبي)',
                    'message' => mb_substr($req->message, 0, 120),
                    'priority' => Notification::PRIORITY_NORMAL,
                    'link_url' => '/doctor/requests',
                    'dedupe_key' => 'demo:'.$this->ns.':request:'.$req->id,
                ], NotificationDelivery::CHANNEL_IN_APP, (string) $doctorUserId, 'demo:'.$this->ns.':request:'.$req->id.':in_app');
            }
        }

        $this->log($log, "Requests done: {$this->counts['requests']}");
    }
}
