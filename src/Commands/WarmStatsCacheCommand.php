<?php

namespace Javaabu\Stats\Commands;

use Illuminate\Console\Command;
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Jobs\WarmStatsCache;
use Javaabu\Stats\StatsManager;

class WarmStatsCacheCommand extends Command
{
    protected $signature = 'stats:warm
        {--queue : Dispatch the WarmStatsCache job instead of warming now}
        {--flush : Invalidate every cached stat first}';

    protected $description = 'Compute the charts registered with Stats::warm() and cache them';

    public function handle(StatsManager $stats): int
    {
        if ($this->option('flush')) {
            $stats->flush();
        }

        if ($this->option('queue')) {
            WarmStatsCache::dispatch();

            $this->components->info('Dispatched the job that warms the stats cache.');

            return self::SUCCESS;
        }

        $count = $stats->warmNow(function (Chart $chart) {
            $this->components->twoColumnDetail($chart->getTitle(), $chart->type());
        });

        $this->components->info("Warmed {$count} " . str('chart')->plural($count) . '.');

        return self::SUCCESS;
    }
}
