<?php

namespace Javaabu\Stats;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsFormatter;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\StatListReturnType;
use Javaabu\Stats\Http\Controllers\Api\TimeSeriesStatsController;

class TimeSeriesStats
{
    protected static array $stats_map = [];
    protected static array $formatters_map = [];

    /**
     * Register a formatter or a number of formatters
     *
     * @param array<string, class-string<TimeSeriesStatsFormatter>> $formatters
     */
    public static function registerFormatters(array $formatters, bool $merge = true)
    {
        static::$formatters_map = $merge ? array_merge(static::$formatters_map, $formatters) : $formatters;
    }

    /**
     * Get the formatters map
     *
     * @return  array<string, class-string<TimeSeriesStatsFormatter>>
     */
    public static function formattersMap(): array
    {
        return static::$formatters_map;
    }

    /**
     * Get the allowed formats
     */
    public static function allowedFormats(): array
    {
        return array_keys(static::formattersMap());
    }

    /**
     * Find the class name of a formatter using its name
     *
     * @return class-string<TimeSeriesStatsFormatter>
     */
    public static function getClassNameForFormat(string $name): string
    {
        return Arr::get(static::$formatters_map, $name, $name);
    }

    /**
     * Get the name for the formatter
     *
     * @param class-string<TimeSeriesStatsFormatter> $formatter
     */
    public static function getNameForFormatter(string $formatter): string
    {
        $name = array_search($formatter, static::$formatters_map, true);

        if (! $name) {
            $name = $formatter;
        }

        return $name;
    }

    /**
     * Create from format
     */
    public static function createFromFormat(string $format): TimeSeriesStatsFormatter
    {
        $class = static::getClassNameForFormat($format);
        return new $class();
    }

    /**
     * Register a stat or a number of stats
     *
     * @param array<string, class-string<TimeSeriesStatsRepository>> $stats
     */
    public static function register(array $stats, bool $merge = true)
    {
        static::$stats_map = $merge ? array_merge(static::$stats_map, $stats) : $stats;
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
    public static function createFromMetric(string $metric, DateRange $date_range = PresetDateRanges::THIS_YEAR, array $filters = []): TimeSeriesStatsRepository
    {
        $class = static::getClassNameForMetric($metric);
        return new $class($date_range, $filters);
    }

    /**
     * Get the metrics that allow these filters
     */
    public static function metricsThatAllowFilters(array|string $filters, ?Authorizable $user = null, StatListReturnType $return_type = StatListReturnType::METRIC_AND_NAME): array
    {
        $metrics = self::statsMap();

        $filters = Arr::wrap($filters);
        $filtered = [];

        foreach ($metrics as $slug => $metric_class) {
            $metric = self::createFromMetric($slug);

            if ($metric->canView($user) && $metric->ensureAllFiltersAllowed($filters)) {
                if ($return_type == StatListReturnType::METRIC) {
                    $filtered[] = $slug;
                } else {
                    $filtered[$slug] = $return_type == StatListReturnType::METRIC_AND_NAME ? $metric->getName() : $metric_class;
                }
            }
        }

        return $filtered;
    }

    /**
     * Get the metric names
     */
    public static function getMetricNames(array|string $filters = [], ?Authorizable $user = null): array
    {
        return self::metricsThatAllowFilters($filters, $user, StatListReturnType::METRIC_AND_NAME);
    }

    /**
     * Get the allowed metric names
     */
    public static function allowedMetrics(array|string $filters = [], ?Authorizable $user = null): array
    {
        return self::metricsThatAllowFilters($filters, $user, StatListReturnType::METRIC);
    }

    /**
     * Get the metric names
     */
    public static function getMetricName(string $metric): string
    {
        $metric = self::createFromMetric($metric);

        return $metric->getName();
    }

    /**
     * Check if the user can view any stats
     */
    public static function canViewAny(?Authorizable $user = null): bool
    {
        $metrics = self::statsMap();

        foreach ($metrics as $slug => $metric_class) {
            $metric = self::createFromMetric($slug);

            if ($metric->canView($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the date locale name
     */
    public static function dateLocale(): string
    {
        return config('stats.date_locale') . '@Stats';
    }

    /**
     * Get the first day of the week
     */
    public static function firstDayOfWeek(): int
    {
        return config('stats.week_starts_on_sunday') ? Carbon::SUNDAY : Carbon::MONDAY;
    }

    /**
     * Get the MySQL week mode
     */
    public static function weekMode(): int
    {
        return static::firstDayOfWeek() == Carbon::SUNDAY ? 6 : 3;
    }

    /**
     * Register the api routes
     */
    public static function registerApiRoute(
        string $url = '/stats/time-series',
        string $name = 'stats.time-series.index',
        array $middleware = ['stats.view-time-series']
    ): \Illuminate\Routing\Route
    {
        return Route::get($url, [TimeSeriesStatsController::class, 'index'])
                    ->name($name)
                    ->middleware($middleware);
    }
}
