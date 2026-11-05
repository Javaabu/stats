<?php

use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Http\Controllers\Api\CategoricalStatsApiController;
use Javaabu\Stats\Http\Controllers\Api\CategoryProviderApiController;
use Javaabu\Stats\Http\Controllers\Api\TimeSeriesStatsApiController;
use Javaabu\Stats\Http\Controllers\CategoricalStatsController;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;
use Javaabu\Stats\Policies\StatsPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Stats
    |--------------------------------------------------------------------------
    |
    | Stats to register, as metric => class. You can also register them from a
    | service provider with TimeSeriesStats::register() and
    | CategoricalStats::register().
    |
    */

    'time_series_stats' => [
        //
    ],

    'categorical_stats' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in stats
    |--------------------------------------------------------------------------
    |
    | Registers user_signups (and user_logins, with spatie/laravel-activitylog)
    | for user_model, or for the model of the default guard's user provider
    | when user_model is null.
    |
    */

    'default_stats' => true,

    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    |
    | week_starts_on_sunday: true for Sunday, false for Monday.
    | date_locale: the locale used to work out weeks.
    | date_formats: how each step is shown, as Carbon isoFormat (moment.js) formats.
    |
    */

    'week_starts_on_sunday' => true,

    'date_locale' => 'en_GB',

    'date_formats' => [
        'hour' => 'D MMM YY hh:mm A',
        'day' => 'D MMM YY',
        'week' => 'gggg - \W\e\e\k w',
        'month' => 'YYYY MMMM',
        'year' => 'YYYY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | What the stats pages and charts use when nothing else is chosen.
    |
    */

    'default_time_series_mode' => TimeSeriesModes::DAY,

    'default_categorical_mode' => CategoricalModes::NON_EMPTY,

    'default_date_range' => PresetDateRanges::LAST_7_DAYS,

    'categorical_items_per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Charts (StatTile, TimeSeriesChart, …) cache their data. Results of
    | periods that ended before today keep for ttl.past seconds; anything that
    | includes today keeps for ttl.current seconds. store null means the app's
    | default cache store. Stats::forget('metric') and the InvalidatesStats
    | model trait clear them.
    |
    | pages: whether the stats pages cache too (off: they show live data).
    | warm: the queue connection and queue of the WarmStatsCache job.
    |
    */

    'cache' => [
        'enabled' => env('STATS_CACHE', true),
        'store' => env('STATS_CACHE_STORE'),
        'prefix' => 'javaabu_stats',
        'ttl' => [
            'current' => 60 * 10,
            'past' => 60 * 60 * 24,
        ],
        'pages' => false,
        'warm' => [
            'connection' => null,
            'queue' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The pages Route::stats() renders: blade (javaabu/forms components and
    | SVG charts) or inertia (React pages with Onu's charts, shipped through
    | javaabu/manifest). The Blade layout the pages extend, and the section
    | they fill.
    |
    | rtl_locales: locales whose Blade charts run right to left.
    |
    */

    'views' => env('STATS_VIEWS', 'blade'),

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    'rtl_locales' => ['dv', 'ar', 'fa', 'he', 'ur'],

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace a controller with a subclass, or pass `controllers` to
    | Route::stats() / Route::statsApi(). The policy here is the package
    | default; apps can also override it with config('permissions.policies')
    | or Gate::policy(). Turn off register_permissions to register your own.
    |
    */

    'controllers' => [
        'time_series' => TimeSeriesStatsController::class,
        'categorical' => CategoricalStatsController::class,
        'time_series_api' => TimeSeriesStatsApiController::class,
        'categorical_api' => CategoricalStatsApiController::class,
        'categories' => CategoryProviderApiController::class,
    ],

    'policy' => StatsPolicy::class,

    'register_permissions' => true,

];
