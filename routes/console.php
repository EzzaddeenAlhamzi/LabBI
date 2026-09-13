<?php

use App\Console\Commands\LabbiBackupCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(LabbiBackupCommand::class, ['--trigger' => 'scheduled'])
    ->dailyAt(config('labbi.backup.schedule_time', '02:15'))
    ->name('labbi-daily-backup')
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::channel('backup')->error('Scheduled daily backup failed');
        Log::channel('scheduler')->error('labbi-daily-backup failed');
    })
    ->onSuccess(function () {
        Log::channel('scheduler')->info('labbi-daily-backup succeeded');
    });
