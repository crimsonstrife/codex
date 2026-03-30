<?php

use App\Console\Commands\DispatchQueueHeartbeatCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app()->booted(function (): void {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $schedule->command(RunHealthChecksCommand::class)->everyMinute();
    $schedule->command(ScheduleCheckHeartbeatCommand::class)->everyMinute();

    if (config('queue.default') !== 'sync') {
        $schedule->command(DispatchQueueHeartbeatCommand::class)->everyMinute();
    }
});
