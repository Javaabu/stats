<?php

namespace Javaabu\Stats;

use Closure;
use Javaabu\Stats\Cache\StatsCache;
use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Charts\PieChart;
use Javaabu\Stats\Charts\Sparkline;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;

/**
 * The `Stats` facade: chart builders, the cache, and the charts to warm
 *
 * ```php
 * Stats::timeSeries('payments_count')->range(PresetDateRanges::LAST_30_DAYS);
 * Stats::warm(fn () => [Stats::tile('payments_count')->compare()]);
 * Stats::forget('payments_count');
 * ```
 */
class StatsManager
{
    /** @var list<Chart|Closure(): (Chart|iterable<Chart>)> */
    protected array $warmables = [];

    public function __construct(protected StatsCache $cache) {}

    public function timeSeries(?string $metric = null, ?string $label = null): TimeSeriesChart
    {
        return TimeSeriesChart::make($metric, $label);
    }

    public function category(?string $metric = null, ?string $label = null): CategoryChart
    {
        return CategoryChart::make($metric, $label);
    }

    public function pie(?string $metric = null, ?string $label = null): PieChart
    {
        return PieChart::make($metric, $label);
    }

    public function sparkline(?string $metric = null, ?string $label = null): Sparkline
    {
        return Sparkline::make($metric, $label);
    }

    public function tile(?string $metric = null, ?string $label = null): StatTile
    {
        return StatTile::make($metric, $label);
    }

    public function cache(): StatsCache
    {
        return $this->cache;
    }

    /**
     * Invalidate the cached results of the metrics (all of them with `*` or none)
     *
     * @param  string|list<string>  $metrics
     */
    public function forget(string|array $metrics = StatsCache::ALL): void
    {
        $this->cache->forget($metrics);
    }

    /**
     * Invalidate every cached stat
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

    /**
     * Charts to keep warm with `stats:warm` or the WarmStatsCache job. Pass the
     * charts, or a closure that returns them so they're built when warmed.
     *
     * @param  Chart|iterable<Chart>|Closure(): (Chart|iterable<Chart>)  $charts
     */
    public function warm(Chart|iterable|Closure $charts): static
    {
        if ($charts instanceof Chart || $charts instanceof Closure) {
            $this->warmables[] = $charts;

            return $this;
        }

        foreach ($charts as $chart) {
            $this->warmables[] = $chart;
        }

        return $this;
    }

    /**
     * The charts registered for warming
     *
     * @return list<Chart>
     */
    public function warmables(): array
    {
        $charts = [];

        foreach ($this->warmables as $warmable) {
            $resolved = $warmable instanceof Closure ? app()->call($warmable) : $warmable;

            foreach ($resolved instanceof Chart ? [$resolved] : $resolved as $chart) {
                $charts[] = $chart;
            }
        }

        return $charts;
    }

    /**
     * Forget the registered charts (for tests)
     */
    public function clearWarmables(): void
    {
        $this->warmables = [];
    }

    /**
     * Compute every registered chart and put it in the cache
     *
     * @param  (Closure(Chart): void)|null  $after  called after each chart
     * @return int the number of charts warmed
     */
    public function warmNow(?Closure $after = null): int
    {
        $count = 0;

        foreach ($this->warmables() as $chart) {
            $chart->refresh();
            $count++;

            if ($after) {
                $after($chart);
            }
        }

        return $count;
    }
}
