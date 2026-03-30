<?php

namespace Tests\Unit;

use Closure;
use Spatie\Health\Health;
use Tests\TestCase;

class AppHealthServiceProviderTest extends TestCase
{
    public function test_it_skips_queue_and_scheduler_checks_in_testing(): void
    {
        $labels = app(Health::class)
            ->registeredChecks()
            ->map(fn ($check) => $check->getLabel())
            ->all();

        $this->assertNotContains('Queue', $labels);
        $this->assertNotContains('Scheduler', $labels);
    }

    public function test_registered_checks_do_not_use_runtime_run_condition_closures(): void
    {
        $closureCount = app(Health::class)
            ->registeredChecks()
            ->sum(function ($check): int {
                $count = 0;

                foreach ($check->getRunConditions() as $condition) {
                    if ($condition instanceof Closure) {
                        $count++;
                    }
                }

                return $count;
            });

        $this->assertSame(0, $closureCount);
    }
}
