---
title: Configuration
sidebar_position: 2
---

```bash
php artisan vendor:publish --tag=stats-config
```

# Stats

| Key | Default | What |
| --- | --- | --- |
| `time_series_stats` | `[]` | Time series stats to register, as metric => class. Registered before the app's service providers boot, so `TimeSeriesStats::register()` calls come after them |
| `categorical_stats` | `[]` | Categorical stats to register, as metric => class |
| `default_stats` | `true` | Register `user_signups` (and `user_logins`, with spatie/laravel-activitylog). See [Time series stats](../basic-usage/time-series-stats.md#built-in-stats) |
| `user_model` | `null` | The user model of the built-in stats. `null`: the model of the default guard's user provider |

```php
'time_series_stats' => [
    'payments_count' => App\Stats\TimeSeries\PaymentsCount::class,
],

'categorical_stats' => [
    'payments_by_island' => App\Stats\Categorical\PaymentsByIsland::class,
],
```

# Dates

| Key | Default | What |
| --- | --- | --- |
| `week_starts_on_sunday` | `true` | `true`: weeks start on Sunday; `false`: Monday. Used to group by week, for `this_week` and `last_week`, and for the steps of week charts |
| `date_locale` | `en_GB` | The locale used to work out weeks (their numbers and the year they belong to) |
| `date_formats` | See below | How each step is shown in chart tables and labels, and by the `chartjs` formatter. Carbon `isoFormat` (moment.js) formats |

```php
'date_formats' => [
    'hour' => 'D MMM YY hh:mm A',
    'day' => 'D MMM YY',
    'week' => 'gggg - \W\e\e\k w',
    'month' => 'YYYY MMMM',
    'year' => 'YYYY',
],
```

Charts label weeks "Week 29, 2024" (a translated string), so `date_formats.week` only affects the formatters. Other steps are formatted in the app's locale, so month names are in Dhivehi when the locale is `dv`.

# Defaults

| Key | Default | What |
| --- | --- | --- |
| `default_time_series_mode` | `TimeSeriesModes::DAY` | The grouping of charts, sparklines and the time series page when none is chosen. An enum case or its value |
| `default_categorical_mode` | `CategoricalModes::NON_EMPTY` | Which categories charts and the categorical page list when none is chosen |
| `default_date_range` | `PresetDateRanges::LAST_7_DAYS` | The range of charts and pages when none is chosen |
| `categorical_items_per_page` | `15` | The page size of the category search |

# Cache

| Key | Default | What |
| --- | --- | --- |
| `cache.enabled` | `env('STATS_CACHE', true)` | Cache chart data |
| `cache.store` | `env('STATS_CACHE_STORE')` | The cache store. `null`: the app's default store |
| `cache.prefix` | `javaabu_stats` | The prefix of the cache keys |
| `cache.ttl.current` | `600` | Seconds to keep data of a period that includes today |
| `cache.ttl.past` | `86400` | Seconds to keep data of a period that ended before today |
| `cache.pages` | `false` | Cache the stats pages' charts too. Off, they show live data |
| `cache.warm.connection` | `null` | The queue connection of the `WarmStatsCache` job. `null`: the default |
| `cache.warm.queue` | `null` | Its queue. `null`: the default |

See [Caching and warming](./caching-and-warming.md).

# Views

| Key | Default | What |
| --- | --- | --- |
| `views` | `env('STATS_VIEWS', 'blade')` | How the stats pages render: `blade` or `inertia` |
| `layout` | `layouts.app` | The Blade layout the pages extend |
| `layout_section` | `content` | The section of the layout they fill |
| `rtl_locales` | `['dv', 'ar', 'fa', 'he', 'ur']` | Locales whose Blade charts run right to left |

With `inertia` and no `inertiajs/inertia-laravel`, the pages throw a `StatsException`.

# Controllers and authorization

| Key | Default | What |
| --- | --- | --- |
| `controllers.time_series` | `TimeSeriesStatsController` | The time series page |
| `controllers.categorical` | `CategoricalStatsController` | The categorical page |
| `controllers.time_series_api` | `Api\TimeSeriesStatsApiController` | The time series JSON endpoint |
| `controllers.categorical_api` | `Api\CategoricalStatsApiController` | The categorical JSON endpoint |
| `controllers.categories` | `Api\CategoryProviderApiController` | The category search |
| `policy` | `StatsPolicy` | The policy of every stat |
| `register_permissions` | `true` | Register `view_stats` with javaabu/permissions. Turn it off to register your own |

The controller classes are in `Javaabu\Stats\Http\Controllers`, the policy in `Javaabu\Stats\Policies`. See [Overriding controllers and views](./overriding-controllers-and-views.md) and [Permissions and policies](./permissions-and-policies.md).

# Environment variables

| Variable | Key |
| --- | --- |
| `STATS_CACHE` | `cache.enabled` |
| `STATS_CACHE_STORE` | `cache.store` |
| `STATS_VIEWS` | `views` |
