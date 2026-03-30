<?php

namespace App\Console\Commands;

use App\Jobs\HealthQueueHeartbeatJob;
use Illuminate\Console\Command;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Health;

class DispatchQueueHeartbeatCommand extends Command
{
    protected $signature = 'codex:health-queue-heartbeat';

    protected $description = 'Dispatch queue heartbeat jobs for all monitored queues.';

    public function handle(Health $health): int
    {
        // Avoid queueing the full QueueCheck object since runtime conditions can
        // include closures that do not round-trip safely through job serialization.
        $queueChecks = $health->registeredChecks()->filter(
            fn (Check $check) => $check instanceof QueueCheck
        );

        foreach ($queueChecks as $queueCheck) {
            foreach ($queueCheck->getQueues() as $queue) {
                HealthQueueHeartbeatJob::dispatch(
                    $queueCheck->getCacheStoreName(),
                    $queueCheck->getHeartbeatCacheKey($queue),
                )->onQueue($queue);
            }
        }

        return self::SUCCESS;
    }
}
