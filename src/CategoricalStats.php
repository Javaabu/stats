<?php

namespace Javaabu\Stats;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Javaabu\Stats\Contracts\CategoricalStatsFormatter;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\StatListReturnType;
use Javaabu\Stats\Http\Controllers\Api\CategoricalStatsApiController;
use Javaabu\Stats\Http\Controllers\Api\CategoryProviderApiController;
use Javaabu\Stats\Http\Controllers\CategoricalStatsController;

class CategoricalStats
{
    /** @var array<string, class-string<CategoricalStatsRepository>> */
    protected static array $stats_map = [];

    /** @var array<string, class-string<CategoricalStatsFormatter>> */
    protected static array $formatters_map = [];

    /**
     * Register a formatter or a number of formatters.
     *
     * @param  array<string, class-string<CategoricalStatsFormatter>>  $formatters
     */
    public static function registerFormatters(array $formatters, bool $merge = true)
    {
        static::$formatters_map = $merge ? array_merge(static::$formatters_map, $formatters) : $formatters;
    }

    /**
     * Get the formatters map.
     *
     * @return array<string, class-string<CategoricalStatsFormatter>>
     */
    public static function formattersMap(): array
    {
        return static::$formatters_map;
    }

    /**
     * Get the allowed formatter names.
     *
     * @return list<string>
     */
    public static function allowedFormats(): array
    {
        return array_keys(static::formattersMap());
    }

    /**
     * Find the formatter class for the given name.
     *
     * @return class-string<CategoricalStatsFormatter>
     */
    public static function getClassNameForFormat(string $name): string
    {
        return Arr::get(static::$formatters_map, $name, $name);
    }

    /**
     * Get the registered name for a formatter class.
     *
     * @param  class-string<CategoricalStatsFormatter>  $formatter
     */
    public static function getNameForFormatter(string $formatter): string
    {
        return array_search($formatter, static::$formatters_map, true) ?: $formatter;
    }

    /**
     * Create a formatter instance.
     */
    public static function createFromFormat(string $format): CategoricalStatsFormatter
    {
        $class = static::getClassNameForFormat($format);

        return new $class;
    }

    /**
     * Register a stat or a number of stats.
     *
     * @param  array<string, class-string<CategoricalStatsRepository>>  $stats
     */
    public static function register(array $stats, bool $merge = true)
    {
        static::$stats_map = $merge ? array_merge(static::$stats_map, $stats) : $stats;
    }

    /**
     * Get the stats map.
     *
     * @return array<string, class-string<CategoricalStatsRepository>>
     */
    public static function statsMap(): array
    {
        return static::$stats_map;
    }

    /**
     * Find the stat class for the given metric.
     *
     * @return class-string<CategoricalStatsRepository>
     */
    public static function getClassNameForMetric(string $metric): string
    {
        return Arr::get(static::$stats_map, $metric, $metric);
    }

    /**
     * Get the registered metric for a stat class.
     *
     * @param  class-string<CategoricalStatsRepository>  $stat
     */
    public static function getMetricForStat(string $stat): string
    {
        return array_search($stat, static::$stats_map, true) ?: $stat;
    }

    /**
     * Create a stat instance.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function createFromMetric(
        string $metric,
        DateRange $date_range = PresetDateRanges::THIS_YEAR,
        array $filters = []
    ): CategoricalStatsRepository {
        $class = static::getClassNameForMetric($metric);

        return new $class($date_range, $filters);
    }

    /**
     * Get metrics visible to the user that allow the supplied filters.
     *
     * @param  array<string, mixed>|list<string>|string  $filters
     * @return array<int|string, string>
     */
    public static function metricsThatAllowFilters(
        array|string $filters,
        ?Authorizable $user = null,
        StatListReturnType $return_type = StatListReturnType::METRIC_AND_NAME
    ): array {
        $filters = Arr::wrap($filters);
        $filtered = [];

        foreach (self::statsMap() as $slug => $metric_class) {
            $metric = self::createFromMetric($slug);

            if ($metric->canView($user) && $metric->ensureAllFiltersAllowed($filters)) {
                if ($return_type == StatListReturnType::METRIC) {
                    $filtered[] = $slug;
                } else {
                    $filtered[$slug] = $return_type == StatListReturnType::METRIC_AND_NAME
                        ? $metric->getName()
                        : $metric_class;
                }
            }
        }

        return $filtered;
    }

    /**
     * Get metric names keyed by metric.
     *
     * @param  array<string, mixed>|list<string>|string  $filters
     * @return array<string, string>
     */
    public static function getMetricNames(array|string $filters = [], ?Authorizable $user = null): array
    {
        return self::metricsThatAllowFilters($filters, $user, StatListReturnType::METRIC_AND_NAME);
    }

    /**
     * Get the allowed metric identifiers.
     *
     * @param  array<string, mixed>|list<string>|string  $filters
     * @return list<string>
     */
    public static function allowedMetrics(array|string $filters = [], ?Authorizable $user = null): array
    {
        return self::metricsThatAllowFilters($filters, $user, StatListReturnType::METRIC);
    }

    public static function getMetricName(string $metric): string
    {
        return self::createFromMetric($metric)->getName();
    }

    public static function canViewAny(?Authorizable $user = null): bool
    {
        return ! empty(self::allowedMetrics([], $user));
    }

    public static function defaultMode(): CategoricalModes
    {
        return config('stats.default_categorical_mode');
    }

    public static function defaultDateRange(): PresetDateRanges
    {
        return config('stats.default_date_range');
    }

    public static function categoricalItemsPerPage(): int
    {
        return max(1, (int) config('stats.categorical_items_per_page'));
    }

    /**
     * Register the categorical API routes.
     *
     * @param  list<string>  $middleware
     */
    public static function registerApiRoutes(
        string $url = '/stats/categorical',
        string $name = 'stats.categorical.index',
        string $categories_name = 'stats.categorical.categories',
        array $middleware = ['stats.view-categorical']
    ): void {
        Route::get($url, [CategoricalStatsApiController::class, 'index'])
            ->name($name)
            ->middleware($middleware);

        Route::get($url.'/categories', [CategoryProviderApiController::class, 'index'])
            ->name($categories_name)
            ->middleware($middleware);
    }

    /**
     * Register the categorical admin routes.
     *
     * @param  list<string>  $middleware
     */
    public static function registerRoutes(
        string $url = '/stats/categorical',
        string $index_name = 'stats.categorical.index',
        string $export_name = 'stats.categorical.export',
        array $middleware = ['stats.view-categorical']
    ): void {
        Route::get($url, [CategoricalStatsController::class, 'index'])
            ->name($index_name)
            ->middleware($middleware);

        Route::post($url, [CategoricalStatsController::class, 'export'])
            ->name($export_name)
            ->middleware($middleware);
    }
}
