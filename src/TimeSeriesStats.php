<?php

namespace Javaabu\Stats;

use Illuminate\Support\Arr;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;

class TimeSeriesStats
{
    protected static array $stats_map = [];

    /**
     * Register a stat or a number of stats
     *
     * @param array<string, class-string<TimeSeriesStatsRepository>> $stats
     */
    public static function register(array $stats)
    {
        static::$stats_map = array_merge(static::$stats_map, $stats);
    }

    /**
     * Get the stats map
     *
     * @return  array<string, class-string<TimeSeriesStatsRepository>>
     */
    public static function statsMap(): array
    {
        return static::$stats_map;
    }

    /**
     * Find the class name of a stat using its metric name
     *
     * @return class-string<TimeSeriesStatsRepository>
     */
    public static function getClassNameForMetric(string $metric): string
    {
        return Arr::get(static::$stats_map, $metric, $metric);
    }

    /**
     * Get the metric name for the stat
     *
     * @param class-string<TimeSeriesStatsRepository> $stat
     */
    public static function getMetricForStat(string $stat): string
    {
        return array_search($stat, static::$stats_map, true);
    }
}
