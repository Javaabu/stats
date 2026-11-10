<?php

namespace Javaabu\Stats;

use Carbon\Translator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\GeneratorHelpers\StubRenderer;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\Stats\Cache\StatsCache;
use Javaabu\Stats\Commands\GenerateCategoricalStatCommand;
use Javaabu\Stats\Commands\GenerateTimeSeriesStatCommand;
use Javaabu\Stats\Commands\WarmStatsCacheCommand;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Formatters\Categorical\ChartjsCategoricalStatsFormatter;
use Javaabu\Stats\Formatters\Categorical\CombinedCategoricalStatsFormatter;
use Javaabu\Stats\Formatters\Categorical\DefaultCategoricalStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\FlotStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\SparklineChartsStatsFormatter;
use Javaabu\Stats\Http\Middleware\AbortIfCannotViewAnyCategoricalStats;
use Javaabu\Stats\Http\Middleware\AbortIfCannotViewAnyTimeSeriesStats;
use Javaabu\Stats\Policies\StatsPolicy;
use Javaabu\Stats\Routing\StatsRoutes;
use Javaabu\Stats\Views\ViewRenderer;

class StatsServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/stats.php', 'stats');

        $this->app->singleton(StatsCache::class);
        $this->app->singleton(StatsManager::class);
        $this->app->singleton(ViewRenderer::class);

        $this->registerMiddlewareAliases();
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stats');

        Blade::componentNamespace('Javaabu\\Stats\\Views\\Components', 'stats');

        StubRenderer::loadStubsFrom(__DIR__ . '/../stubs', 'stats');

        Translator::get(TimeSeriesStats::dateLocale())->setTranslations([
            'first_day_of_week' => TimeSeriesStats::firstDayOfWeek(),
        ]);

        $this->registerFrontendAssets();
        $this->registerFormatters();
        $this->registerStats();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                GenerateCategoricalStatCommand::class,
                GenerateTimeSeriesStatCommand::class,
                WarmStatsCacheCommand::class,
            ]);
        }
    }

    /**
     * The chart styles for Blade, the Inertia pages (stats::TimeSeries/Index,
     * stats::Categorical/Index) and the components apps import as @javaabu/stats
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets('stats', __DIR__ . '/../resources', function (Package $package) {
            $package
                ->js()
                ->pages()
                ->dist('../dist')
                ->style('charts', 'css/charts.css')
                // The chart styles import nothing; only the React pages and
                // @javaabu/stats imports use these, so Blade apps aren't asked for them
                ->npm([
                    '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                    '@javaabu/onu' => '^0.2.1',
                    'recharts' => '^3.10.1',
                ], peer_dependencies: [
                    'react' => '^19.0',
                    'react-dom' => '^19.0',
                    '@inertiajs/react' => '^2.0 || ^3.0',
                ], for: 'pages');
        });
    }

    protected function registerFormatters(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class,
            'chartjs' => ChartjsStatsFormatter::class,
            'sparkline' => SparklineChartsStatsFormatter::class,
            'flot' => FlotStatsFormatter::class,
            'combined' => CombinedStatsFormatter::class,
        ]);

        CategoricalStats::registerFormatters([
            'default' => DefaultCategoricalStatsFormatter::class,
            'chartjs' => ChartjsCategoricalStatsFormatter::class,
            'combined' => CombinedCategoricalStatsFormatter::class,
        ]);
    }

    /**
     * The stats in the config, and the built-in ones
     */
    protected function registerStats(): void
    {
        if ($stats = (array) config('stats.time_series_stats', [])) {
            TimeSeriesStats::register($stats);
        }

        if ($stats = (array) config('stats.categorical_stats', [])) {
            CategoricalStats::register($stats);
        }

        if (config('stats.default_stats', true) && ! TimeSeriesStats::shouldExcludeDefaultStats()) {
            TimeSeriesStats::registerDefaultStats();
        }
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('stats', function (array $options = []) {
            StatsRoutes::register($this, $options);
        });

        Route::macro('statsApi', function (array $options = []) {
            StatsRoutes::registerApi($this, $options);
        });
    }

    /**
     * view_stats, and the default policy for every stat, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (config('stats.register_permissions', true)) {
            Permissions::register('stats', [
                StatsPolicy::VIEW => 'View stats',
            ], label: 'Stats');
        }

        $policy = config('stats.policy', StatsPolicy::class);

        Permissions::policy(TimeSeriesStatsRepository::class, $policy);
        Permissions::policy(CategoricalStatsRepository::class, $policy);
    }

    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('stats.view-time-series', AbortIfCannotViewAnyTimeSeriesStats::class);
        $router->aliasMiddleware('stats.view-categorical', AbortIfCannotViewAnyCategoricalStats::class);
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/stats.php' => config_path('stats.php'),
        ], 'stats-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/stats'),
        ], 'stats-views');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/stats'),
        ], 'stats-translations');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/vendor/stats'),
        ], 'stats-stubs');
    }
}
