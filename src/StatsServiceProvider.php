<?php

namespace Javaabu\Stats;

use Illuminate\Support\ServiceProvider;

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

        \Carbon\Translator::get(TimeSeriesStats::dateLocale())->setTranslations([
            'first_day_of_week' => TimeSeriesStats::firstDayOfWeek(),
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/stats.php', 'stats');
    }
}
