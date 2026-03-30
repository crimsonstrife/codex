<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;

class AppHealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            Health::checks([
                DatabaseCheck::new()
                    ->name('Database')
                    ->everyMinute(),

                CacheCheck::new()
                    ->name('Cache')
                    ->everyFiveMinutes(),

                QueueCheck::new()
                    ->name('Queue')
                    ->everyFiveMinutes()
                    ->unless(fn (): bool => app()->environment('testing') || config('queue.default') === 'sync'),

                ScheduleCheck::new()
                    ->name('Scheduler')
                    ->everyMinute()
                    ->unless(fn (): bool => app()->environment(['local', 'testing'])),

                OptimizedAppCheck::new()
                    ->name('Optimization')
                    ->everyThirtyMinutes(),

                UsedDiskSpaceCheck::new()
                    ->name('Disk Usage')
                    ->warnWhenUsedSpaceIsAbovePercentage(70)
                    ->failWhenUsedSpaceIsAbovePercentage(90)
                    ->daily(),

                CpuLoadCheck::new()
                    ->name('CPU Load')
                    ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
                    ->failWhenLoadIsHigherInTheLast15Minutes(1.5),

                SecurityAdvisoriesCheck::new()
                    ->name('Security Advisories')
                    ->daily(),
            ]);
        });
    }
}
