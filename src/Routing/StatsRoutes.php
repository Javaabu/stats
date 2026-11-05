<?php

namespace Javaabu\Stats\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Stats\Http\Controllers\Api\CategoricalStatsApiController;
use Javaabu\Stats\Http\Controllers\Api\CategoryProviderApiController;
use Javaabu\Stats\Http\Controllers\Api\TimeSeriesStatsApiController;
use Javaabu\Stats\Http\Controllers\CategoricalStatsController;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;

/**
 * Registers the stats pages for `Route::stats()` and the JSON API for `Route::statsApi()`
 *
 * ```php
 * Route::stats();                                                        // /stats/..., stats.*, auth
 * Route::stats(['guard' => 'web_admin', 'prefix' => 'admin/stats', 'name' => 'admin.stats']);
 * Route::statsApi(['middleware' => ['auth:api']]);                       // /stats/..., api.stats.*
 * ```
 *
 * `Route::stats()`:
 *
 * | Route                                | Name                               | Action          |
 * | ------------------------------------ | ---------------------------------- | --------------- |
 * | GET {prefix}/time-series             | {name}.time-series.index           | index           |
 * | POST {prefix}/time-series            | {name}.time-series.export          | export (CSV)    |
 * | GET {prefix}/categorical             | {name}.categorical.index           | index           |
 * | POST {prefix}/categorical            | {name}.categorical.export          | export (CSV)    |
 * | GET {prefix}/categorical/categories  | {name}.categorical.categories      | category search |
 *
 * `Route::statsApi()` registers `GET {prefix}/time-series`, `GET {prefix}/categorical`
 * and `GET {prefix}/categorical/categories`, named `{name}.time-series.index`,
 * `{name}.categorical.index` and `{name}.categorical.categories`.
 */
class StatsRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'stats_guard';

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     time_series?: bool,
     *     categorical?: bool,
     *     controllers?: array{time_series?: class-string, categorical?: class-string, categories?: class-string}
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $controllers = $options['controllers'] ?? [];

        $time_series = $controllers['time_series'] ?? config('stats.controllers.time_series', TimeSeriesStatsController::class);
        $categorical = $controllers['categorical'] ?? config('stats.controllers.categorical', CategoricalStatsController::class);
        $categories = $controllers['categories'] ?? config('stats.controllers.categories', CategoryProviderApiController::class);

        $router->group(static::groupAttributes($options, 'stats', 'stats', [$guard ? "auth:{$guard}" : 'auth']), function (Router $router) use ($options, $time_series, $categorical, $categories) {
            if ($options['time_series'] ?? true) {
                $router->get('time-series', [$time_series, 'index'])->name('time-series.index');
                $router->post('time-series', [$time_series, 'export'])->name('time-series.export');
            }

            if ($options['categorical'] ?? true) {
                $router->get('categorical', [$categorical, 'index'])->name('categorical.index');
                $router->post('categorical', [$categorical, 'export'])->name('categorical.export');
                $router->get('categorical/categories', [$categories, 'index'])->name('categorical.categories');
            }
        });
    }

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     time_series?: bool,
     *     categorical?: bool,
     *     controllers?: array{time_series_api?: class-string, categorical_api?: class-string, categories?: class-string}
     * }  $options
     */
    public static function registerApi(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $controllers = $options['controllers'] ?? [];

        $time_series = $controllers['time_series_api'] ?? config('stats.controllers.time_series_api', TimeSeriesStatsApiController::class);
        $categorical = $controllers['categorical_api'] ?? config('stats.controllers.categorical_api', CategoricalStatsApiController::class);
        $categories = $controllers['categories'] ?? config('stats.controllers.categories', CategoryProviderApiController::class);

        $router->group(static::groupAttributes($options, 'stats', 'api.stats', [$guard ? "auth:{$guard}" : 'auth']), function (Router $router) use ($options, $time_series, $categorical, $categories) {
            if ($options['time_series'] ?? true) {
                $router->get('time-series', [$time_series, 'index'])->name('time-series.index');
            }

            if ($options['categorical'] ?? true) {
                $router->get('categorical', [$categorical, 'index'])->name('categorical.index');
                $router->get('categorical/categories', [$categories, 'index'])->name('categorical.categories');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $default_middleware
     * @return array<string, mixed>
     */
    protected static function groupAttributes(array $options, string $default_prefix, string $default_name, array $default_middleware): array
    {
        return [
            'prefix' => trim($options['prefix'] ?? $default_prefix, '/'),
            'as' => rtrim($options['name'] ?? $default_name, '.') . '.',
            'middleware' => $options['middleware'] ?? $default_middleware,
            self::GUARD_ACTION => $options['guard'] ?? null,
        ];
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.stats` for `admin.stats.time-series.index`
     */
    public static function namePrefix(Request $request, string $default = 'stats'): string
    {
        $name = (string) $request->route()?->getName();

        foreach (['.time-series.', '.categorical.'] as $segment) {
            if (str_contains($name, $segment)) {
                return substr($name, 0, strpos($name, $segment));
            }
        }

        return $default;
    }
}
