<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class HealthQueueHeartbeatJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly string $cacheStoreName,
        public readonly string $heartbeatCacheKey,
    ) {}

    public function handle(): void
    {
        cache()
            ->store($this->cacheStoreName)
            ->set($this->heartbeatCacheKey, now()->timestamp);
    }
}
