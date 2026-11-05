<?php

namespace Javaabu\Stats\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Javaabu\Stats\StatsManager;

/**
 * Computes every chart registered with `Stats::warm()` and puts it in the
 * cache, so dashboards load from the cache. Schedule it:
 *
 * ```php
 * Schedule::job(new WarmStatsCache)->everyFiveMinutes();
 * ```
 */
class WarmStatsCache implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $uniqueFor = 600;

    public function __construct()
    {
        $this->onConnection(config('stats.cache.warm.connection'));
        $this->onQueue(config('stats.cache.warm.queue'));
    }

    public function handle(StatsManager $stats): void
    {
        $stats->warmNow();
    }
}
