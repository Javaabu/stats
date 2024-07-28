<?php

namespace Javaabu\Stats;

use Illuminate\Support\ServiceProvider;
use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\FlotStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\SparklineChartsStatsFormatter;
use Javaabu\Stats\Http\Middleware\AbortIfCannotViewAnyTimeSeriesStats;
use Javaabu\Stats\Repositories\TimeSeries\UserSignupsRepository;

class StatsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/stats.php' => config_path('stats.php'),
            ], 'stats-config');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stats');

        \Carbon\Translator::get(TimeSeriesStats::dateLocale())->setTranslations([
            'first_day_of_week' => TimeSeriesStats::firstDayOfWeek(),
        ]);

        $this->registerFormatters();

        $this->registerDefaultStats();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/stats.php', 'stats');

        $this->registerMiddlewareAliases();
    }

    protected function registerFormatters()
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class,
            'chartjs' => ChartjsStatsFormatter::class,
            'sparkline' => SparklineChartsStatsFormatter::class,
            'flot' => FlotStatsFormatter::class,
            'combined' => CombinedStatsFormatter::class,
        ]);
    }

    protected function registerDefaultStats()
    {
        if (class_exists(\App\Models\User::class)) {
            TimeSeriesStats::register([
                'user_signups' => UserSignupsRepository::class,
            ]);
        }
    }

    protected function registerMiddlewareAliases()
    {
        app('router')->aliasMiddleware('stats.view-time-series', AbortIfCannotViewAnyTimeSeriesStats::class);
    }
}
