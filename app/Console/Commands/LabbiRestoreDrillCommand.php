<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\Patient;
use App\Models\User;
use App\Services\Ops\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * AC-23 restore drill: create → artifact → restore → verify core records.
 * Safe for automated tests / staging. Prefer a dedicated DB.
 */
class LabbiRestoreDrillCommand extends Command
{
    protected $signature = 'labbi:restore-drill {--force : Required confirmation}';

    protected $description = 'Run a destructive restore drill against the current database';

    public function handle(BackupService $backups): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to run without --force (destructive).');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! (bool) env('ALLOW_RESTORE_DRILL_IN_PRODUCTION', false)) {
            $this->error('Restore drill blocked in production unless ALLOW_RESTORE_DRILL_IN_PRODUCTION=true.');

            return self::FAILURE;
        }

        try {
            $marker = 'RESTORE-DRILL-'.now()->format('YmdHis');
            $patient = Patient::query()->create([
                'medical_file_number' => $marker,
                'full_name' => 'مريض تجربة الاستعادة',
                'phone' => '0500000099',
                'national_id' => 'DRILL-NID-001',
            ]);

            $this->info("Created drill patient #{$patient->id} ({$marker})");

            $backup = $backups->create(null, 'drill', 'RESTORE DRILL artifact');
            $this->info("Created backup #{$backup->id} → {$backup->storage_path}");

            $patient->delete();
            $this->warn('Deleted drill patient (soft) to prove restore.');

            $actor = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'admin'))->first()
                ?? User::query()->create([
                    'name' => 'Restore Drill Admin',
                    'email' => 'restore-drill@labbi.local',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);

            $backups->restore($backup, $actor, confirmed: true);

            $restored = Patient::withTrashed()->where('medical_file_number', $marker)->first();
            if (! $restored) {
                $this->error('RESTORE DRILL FAILED: marker patient missing after restore.');

                return self::FAILURE;
            }

            $this->info('RESTORE DRILL PASSED: marker patient present after restore.');
            $this->line('backup_id='.$backup->id);
            $this->line('patient_id='.$restored->id);
            $this->line('medical_file_number='.$restored->medical_file_number);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('RESTORE DRILL FAILED: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
