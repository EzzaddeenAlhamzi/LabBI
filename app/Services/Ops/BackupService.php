<?php

namespace App\Services\Ops;

use App\Models\Backup;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    /** @var list<string> Directories under the private disk to include. */
    public const FILE_PREFIXES = [
        'attachments',
        'reports',
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function diskName(): string
    {
        return (string) config('labbi.backup.disk', env('BACKUP_DISK', 'local'));
    }

    public function create(?User $actor = null, string $trigger = 'manual', ?string $notes = null): Backup
    {
        $disk = $this->diskName();
        $backup = Backup::query()->create([
            'taken_at' => now(),
            'status' => 'pending',
            'disk' => $disk,
            'trigger' => $trigger,
            'created_by' => $actor?->id,
            'notes' => $notes,
        ]);

        try {
            $relativeDir = 'backups/'.now()->format('Y/m');
            $baseName = 'labbi-backup-'.$backup->id.'-'.now()->format('YmdHis');
            $zipRelative = $relativeDir.'/'.$baseName.'.zip';

            Storage::disk($disk)->makeDirectory($relativeDir);

            $absoluteZip = $this->absolutePath($disk, $zipRelative);
            $tmpDir = storage_path('app/tmp/backup-'.$backup->id);
            if (! is_dir($tmpDir)) {
                mkdir($tmpDir, 0750, true);
            }

            $dbDumpPath = $tmpDir.'/database.json';
            $manifest = $this->dumpDatabase($dbDumpPath);
            $filesCopied = $this->collectPrivateFiles($tmpDir.'/files');

            $zip = new ZipArchive;
            if ($zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create backup archive.');
            }

            $zip->addFile($dbDumpPath, 'database.json');
            $zip->addFromString('manifest.json', json_encode([
                'created_at' => now()->toIso8601String(),
                'app' => config('app.name'),
                'trigger' => $trigger,
                'tables' => $manifest['tables'],
                'row_counts' => $manifest['row_counts'],
                'files' => $filesCopied,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            foreach ($filesCopied as $relative) {
                $local = $tmpDir.'/files/'.$relative;
                if (is_file($local)) {
                    $zip->addFile($local, 'files/'.$relative);
                }
            }

            $zip->close();

            $size = filesize($absoluteZip) ?: 0;

            $backup->forceFill([
                'status' => 'success',
                'storage_path' => $zipRelative,
                'size_bytes' => $size,
                'manifest' => [
                    'tables' => $manifest['tables'],
                    'row_counts' => $manifest['row_counts'],
                    'files' => $filesCopied,
                ],
                'notes' => $notes,
            ])->save();

            $this->audit->log('backups.created', $backup, null, [
                'trigger' => $trigger,
                'disk' => $disk,
                'size_bytes' => $size,
                'storage_path' => $zipRelative,
            ], $actor?->id);

            Log::channel('backup')->info('Backup created', [
                'backup_id' => $backup->id,
                'size_bytes' => $size,
                'trigger' => $trigger,
            ]);

            $this->cleanupDir($tmpDir);

            return $backup->fresh();
        } catch (Throwable $e) {
            $backup->forceFill([
                'status' => 'failed',
                'error_summary' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            Log::channel('backup')->error('Backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            $this->audit->log('backups.failed', $backup, null, [
                'error' => mb_substr($e->getMessage(), 0, 200),
            ], $actor?->id);

            throw $e;
        }
    }

    /**
     * Destructive restore into the current database connection.
     * Intended for drill/staging or controlled production recovery.
     */
    public function restore(Backup $backup, User $actor, bool $confirmed = false): Backup
    {
        if (! $confirmed) {
            throw new RuntimeException('Restore requires explicit confirmation.');
        }

        if (! $backup->isSuccessful() || ! $backup->storage_path) {
            throw new RuntimeException('Backup artifact is not restorable.');
        }

        $disk = $backup->disk ?: $this->diskName();
        $absoluteZip = $this->absolutePath($disk, $backup->storage_path);
        if (! is_file($absoluteZip)) {
            throw new RuntimeException('Backup archive missing on disk.');
        }

        $tmpDir = storage_path('app/tmp/restore-'.$backup->id.'-'.uniqid());
        mkdir($tmpDir, 0750, true);

        $zip = new ZipArchive;
        if ($zip->open($absoluteZip) !== true) {
            throw new RuntimeException('Unable to open backup archive.');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $dbDump = $tmpDir.'/database.json';
        if (! is_file($dbDump)) {
            throw new RuntimeException('Backup archive missing database.json.');
        }

        $payload = json_decode((string) file_get_contents($dbDump), true, 512, JSON_THROW_ON_ERROR);
        $this->importDatabase($payload);

        $filesRoot = $tmpDir.'/files';
        if (is_dir($filesRoot)) {
            $this->restorePrivateFiles($filesRoot);
        }

        $backup->forceFill([
            'status' => 'restored',
            'restored_at' => now(),
            'restored_by' => $actor->id,
            'notes' => trim(($backup->notes ? $backup->notes."\n" : '').'Restored by user #'.$actor->id),
        ])->save();

        $this->audit->log('backups.restored', $backup, null, [
            'storage_path' => $backup->storage_path,
            'warning' => 'destructive_restore',
        ], $actor->id);

        Log::channel('backup')->warning('Backup restored', [
            'backup_id' => $backup->id,
            'actor_id' => $actor->id,
        ]);

        $this->cleanupDir($tmpDir);

        return $backup->fresh();
    }

    /**
     * @return array{tables: list<string>, row_counts: array<string, int>}
     */
    protected function dumpDatabase(string $targetPath): array
    {
        $tables = $this->listTables();
        $data = [
            'version' => 1,
            'connection' => config('database.default'),
            'dumped_at' => now()->toIso8601String(),
            'tables' => [],
        ];
        $rowCounts = [];

        foreach ($tables as $table) {
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $data['tables'][$table] = $rows;
            $rowCounts[$table] = count($rows);
        }

        file_put_contents(
            $targetPath,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return [
            'tables' => array_keys($data['tables']),
            'row_counts' => $rowCounts,
        ];
    }

    /**
     * @param  array{tables: array<string, list<array<string, mixed>>>}  $payload
     */
    protected function importDatabase(array $payload): void
    {
        $tables = $payload['tables'] ?? [];
        if ($tables === []) {
            throw new RuntimeException('Empty backup database payload.');
        }

        // Prefer explicit dependency order — required under SQLite + RefreshDatabase
        // transactions where PRAGMA foreign_keys=OFF has no effect.
        $order = [
            'users',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'roles',
            'permissions',
            'role_user',
            'permission_role',
            'departments',
            'doctors',
            'laboratory_staff',
            'patients',
            'tests',
            'test_reference_ranges',
            'test_orders',
            'test_order_items',
            'samples',
            'results',
            'result_reviews',
            'result_attachments',
            'critical_alerts',
            'notifications',
            'notification_deliveries',
            'generated_reports',
            'patient_requests',
            'audit_logs',
            'backups',
        ];

        $ordered = array_values(array_unique(array_merge(
            array_values(array_filter($order, fn ($t) => array_key_exists($t, $tables))),
            array_keys($tables),
        )));

        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_reverse($ordered) as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            foreach ($ordered as $table) {
                $rows = $tables[$table] ?? [];
                if (! Schema::hasTable($table) || $rows === []) {
                    continue;
                }

                foreach (array_chunk($rows, 50) as $chunk) {
                    $normalized = array_map(function (array $row) {
                        foreach ($row as $key => $value) {
                            if ($value === '') {
                                // Keep empty strings; nullable dates often stored as null in dump.
                                continue;
                            }
                        }

                        return $row;
                    }, $chunk);

                    DB::table($table)->insert($normalized);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @return list<string>
     */
    protected function collectPrivateFiles(string $targetDir): array
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0750, true);
        }

        $copied = [];
        $disk = Storage::disk('local');

        foreach (self::FILE_PREFIXES as $prefix) {
            if (! $disk->exists($prefix)) {
                continue;
            }

            foreach ($disk->allFiles($prefix) as $path) {
                $contents = $disk->get($path);
                $dest = $targetDir.'/'.$path;
                $dir = dirname($dest);
                if (! is_dir($dir)) {
                    mkdir($dir, 0750, true);
                }
                file_put_contents($dest, $contents);
                $copied[] = $path;
            }
        }

        return $copied;
    }

    protected function restorePrivateFiles(string $filesRoot): void
    {
        $disk = Storage::disk('local');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filesRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $relative = ltrim(str_replace($filesRoot, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace('\\', '/', $relative);
            $disk->put($relative, file_get_contents($file->getPathname()));
        }
    }

    /**
     * @return list<string>
     */
    protected function listTables(): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            return array_values(array_filter(array_map(fn ($r) => $r->name, $rows), fn ($n) => $n !== 'migrations'));
        }

        $database = DB::getDatabaseName();
        $rows = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', [$database]);

        return array_values(array_filter(array_map(fn ($r) => $r->name, $rows), fn ($n) => $n !== 'migrations'));
    }

    protected function absolutePath(string $disk, string $relative): string
    {
        $root = config("filesystems.disks.{$disk}.root");
        if (! $root) {
            throw new RuntimeException("Disk [{$disk}] has no local root.");
        }

        if (! is_dir($root)) {
            mkdir($root, 0750, true);
        }

        $dir = dirname($root.'/'.$relative);
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $root.'/'.$relative;
    }

    protected function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
