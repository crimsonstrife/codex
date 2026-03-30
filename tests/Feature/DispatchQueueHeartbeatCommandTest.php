<?php

namespace Tests\Feature;

use App\Jobs\HealthQueueHeartbeatJob;
use Illuminate\Support\Facades\Bus;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Health;
use Tests\TestCase;

class DispatchQueueHeartbeatCommandTest extends TestCase
{
    public function test_it_dispatches_primitive_queue_heartbeat_jobs_even_when_checks_have_runtime_closures(): void
    {
        Bus::fake();

        /** @var Health $health */
        $health = app(Health::class);

        $health->clearChecks()->checks([
            QueueCheck::new()
                ->name('Queue')
                ->useCacheStore('array')
                ->onQueue(['default', 'critical'])
                ->unless(fn () => false),
        ]);

        $this->artisan('codex:health-queue-heartbeat')
            ->assertSuccessful();

        Bus::assertDispatchedTimes(HealthQueueHeartbeatJob::class, 2);

        Bus::assertDispatched(HealthQueueHeartbeatJob::class, function (HealthQueueHeartbeatJob $job): bool {
            return $job->cacheStoreName === 'array'
                && $job->heartbeatCacheKey === 'health:checks:queue:latestHeartbeatAt.default';
        });

        Bus::assertDispatched(HealthQueueHeartbeatJob::class, function (HealthQueueHeartbeatJob $job): bool {
            return $job->cacheStoreName === 'array'
                && $job->heartbeatCacheKey === 'health:checks:queue:latestHeartbeatAt.critical';
        });
    }
}
