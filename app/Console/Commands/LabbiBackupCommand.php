<?php

namespace App\Console\Commands;

use App\Services\Ops\BackupService;
use Illuminate\Console\Command;
use Throwable;

class LabbiBackupCommand extends Command
{
    protected $signature = 'labbi:backup {--trigger=scheduled : scheduled|manual|drill} {--notes=}';

    protected $description = 'Create a LabBI database + private-files backup archive';

    public function handle(BackupService $backups): int
    {
        $trigger = (string) $this->option('trigger');
        $notes = $this->option('notes');

        try {
            $backup = $backups->create(null, $trigger, $notes ? (string) $notes : null);
            $this->info("Backup #{$backup->id} {$backup->status} size={$backup->size_bytes} path={$backup->storage_path}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
