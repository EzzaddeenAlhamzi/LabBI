<?php

namespace Database\Seeders;

use App\Services\Demo\LabBIDemoSeedingService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Opt-in DEVELOPMENT demo volume seeder.
 *
 *   LABBI_DEMO_PROFILE=small|medium|large LABBI_DEMO_SEED=42 \
 *     php artisan db:seed --class=LabBIDemoSeeder
 *
 * NOT called from DatabaseSeeder. Refuses production.
 * Preserves canonical admins/roles/passwords. Synthetic PHI only.
 * Reference ranges remain demo-only until CAT-04 medical sign-off.
 */
class LabBIDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LabBIDemoSeeder refuses APP_ENV=production. Aborting.');
        }

        $this->command?->warn('LabBI demo seeding — synthetic data only; CAT-04 ranges are demo-only.');
        $this->command?->warn('Mail faked; WhatsApp uses mock driver. Canonical *@labbi.local passwords preserved.');

        $summary = app(LabBIDemoSeedingService::class)->run(
            function (string $message): void {
                $this->command?->info($message);
            }
        );

        $this->command?->newLine();
        $this->command?->info('=== LabBI demo seeding summary ===');
        $this->command?->info('Profile: '.$summary['profile'].'  Seed: '.$summary['seed']);
        foreach ($summary['counts'] as $key => $value) {
            $this->command?->info(sprintf('  %-15s %d', $key, $value));
        }
    }
}
