<?php

namespace Javaabu\Stats;

use Illuminate\Support\Arr;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;

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
        $metric = array_search($stat, static::$stats_map, true);

        if (! $metric) {
            $metric = $stat;
        }

        return $metric;
    }

    /**
     * Create from metric
     */
    public static function createFromMetric(string $metric, DateRange $date_range = PresetDateRanges::LIFETIME, array $filters = []): TimeSeriesStatsRepository
    {
        $class = static::getClassNameForMetric($metric);
        return new $class($date_range, $filters);
    }

    /**
     * Get the metrics that allow these filters
     */
    public static function metricsThatAllowFilters(array|string $filters): array
    {
        $metrics = self::statsMap();

        $filters = Arr::wrap($filters);
        $filtered = [];

        foreach ($metrics as $slug => $data) {
            $metric = self::createFromMetric($slug);

            $allowed_filters = $metric->allowedFilters();
            $allowed = true;

            foreach ($filters as $filter) {
                if (! in_array($filter, $allowed_filters)) {
                    $allowed = false;
                    break;
                }
            }

            if ($metric) {
                $filtered[$slug] = $data;
            }
        }

        return $filtered;
    }
}
