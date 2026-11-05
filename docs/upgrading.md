---
title: Upgrading from v1
sidebar_position: 1.9
---

# Upgrading from v1 to v2

v2 keeps the way you define stats: `TimeSeriesStats::register()`, `CategoricalStats::register()`, the `CountStatsRepository` / `SumStatsRepository` families, `StatsFilter`, the date ranges, the modes, the category providers and the formatters all work as before. Most of the changes are around them: the front end, the dependencies, the database support and the routes.

Work through the sections below in order. Each one says what changed, why, and what to do.

## Requirements

| | v1 | v2 |
| --- | --- | --- |
| PHP | 8.1+ | 8.4+ |
| Laravel | 9–13 | 12 or 13 |
| javaabu/forms | ^1.68 | ^2.0 |
| Front end | jQuery, Select2, Chart.js / Flot, material-admin views | Blade with SVG charts, or Inertia React with [Onu](https://github.com/Javaabu/onu)'s charts |

```bash
composer require javaabu/stats:^2.0
```

While the next-gen packages are unreleased, add the VCS repositories listed in [Installation](./installation-and-setup.md) and require `^2.0@dev`.

## Removed dependencies

v2 no longer requires:

- **javaabu/helpers.** It pins javaabu/settings ^1, which can't be installed next to settings v2. The enums now have their own `getLabel()` and `getLabels()` (`Javaabu\Stats\Enums\Concerns\HasLabels`) instead of `IsEnum` and `NativeEnumsTrait`. The controllers extend `Illuminate\Routing\Controller`.
- **javaabu/settings.** Exports used `get_setting('app_name')` for the file name; they now use `config('app.name')`.
- **maatwebsite/excel.** Exports are plain CSV, streamed with a UTF-8 byte order mark so spreadsheet apps show Thaana. `TimeSeriesStatsExport` and `CategoricalStatsExport` keep `array()`, `headings()`, `map()`, `getReportTitle()` and `formattedDateRange()`, and gain `download($file_name)`, `toCsv()` and `lines()`. They no longer implement Laravel Excel's concerns, so `Excel::download(new TimeSeriesStatsExport(...))` becomes `(new TimeSeriesStatsExport(...))->download($file_name)`, and `Excel::fake()` in your tests becomes an assertion on the response (`assertDownload()`, `streamedContent()`).

If your app used any of these packages itself, require them in your app's `composer.json`.

## Views and front end

The `material-admin-26` views, the `stats.framework` config, and the `<x-stats::time-series>` and `<x-stats::categorical>` components are gone, with the jQuery, Select2 and Chart.js scripts they needed.

In their place:

- **Pages.** `Route::stats()` registers a time series page and a categorical page. They are plain GET forms (defined with javaabu/stencil) that the server answers with the chart, so the Blade pages need no JavaScript of their own. `config('stats.views')` picks Blade or Inertia React. See [Stats pages](./basic-usage/stats-pages.md).
- **Charts anywhere.** `TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline` and `StatTile` describe a chart in PHP. Blade draws it as SVG (`<x-stats::chart :chart="$chart" />`), and React draws it with Onu (`<StatsChart chart={chart} />`). See [Charts](./basic-usage/charts.md).

| v1 | v2 |
| --- | --- |
| `config('stats.framework')` | Removed. Blade uses javaabu/forms v2 components, which follow `config('forms.framework')` |
| `config('stats.default_layout')` | `config('stats.layout')` and `config('stats.layout_section')` |
| `config('stats.scripts_stack')` | Removed: the pages need no scripts; javaabu/manifest adds the chart styles |
| `config('stats.time_series_stats_view')`, `categorical_stats_view` | Removed. Publish the views (`stats-views` tag) or extend the controller |
| `<x-stats::time-series :filters="[...]" />` on a model's page | A page for the model: extend `TimeSeriesStatsController` and return the filters from `filters()`; or a chart with `->filters([...])`. See [Stats pages](./basic-usage/stats-pages.md#stats-for-one-model) |
| `<x-stats::categorical ... />` | The same, with `CategoricalStatsController` or `CategoryChart` |
| `resources/views/vendor/stats/material-admin-26/...` | Delete them; publish the new views if you need to change them |

If you had published the v1 views, delete `resources/views/vendor/stats/material-admin-26`.

## Routes

v1 registered routes with static helpers. v2 has route macros that take a prefix, a name, a guard and middleware, so the pages work for any guard:

```php
// v1
TimeSeriesStats::registerRoutes();
TimeSeriesStats::registerApiRoute();
CategoricalStats::registerRoutes();
CategoricalStats::registerApiRoutes();

// v2
Route::stats(['prefix' => 'admin/stats', 'name' => 'admin.stats', 'guard' => 'web_admin']);
Route::statsApi(['middleware' => ['api', 'auth:api']]);
```

The v1 helpers still work and are deprecated. They now use the controllers in `config('stats.controllers')`. The `stats.view-time-series` and `stats.view-categorical` middleware still exist, but the v2 routes don't need them: the controllers check the stats themselves.

## Authorization

In v1 a stat's `canView()` checked `$user->can('view_stats')`. In v2 it asks the gate (`Gate::forUser($user)->allows('view', $stat)`), which uses `StatsPolicy`, which checks `view_stats`. The result is the same unless you change the policy.

- `view_stats` is now registered with javaabu/permissions (group "Stats"). Run your permission sync so the permission and its Dhivehi description exist. Turn this off with `stats.register_permissions`.
- Replace the policy with `config('stats.policy')`, `config('permissions.policies')` or `Gate::policy(TimeSeriesStatsRepository::class, ...)`.
- Stats that override `canView()` keep working as they are.
- Downloads also check the policy's `export` ability, which by default allows whoever the stat's `canView()` allows.

## Built-in stats

v1 registered `user_signups` (and `user_logins` with spatie/laravel-activitylog) whenever `App\Models\User` existed. v2 registers them when `config('stats.default_stats')` is on (the default) for the model in `config('stats.user_model')`, or, when that's null, the model of the default guard's user provider. Their names changed from "Admin User Signups" and "Admin User Logins" to "User Signups" and "User Logins". `TimeSeriesStats::excludeDefaultStats()` still turns them off.

## Databases

v1's date grouping only worked on MySQL. v2 groups dates on MySQL, MariaDB, PostgreSQL and SQLite, and throws a `StatsException` on other drivers. To keep the results the same everywhere, a few keys changed:

| Mode | v1 key | v2 key |
| --- | --- | --- |
| `hour` | `2024-07-16 11:00` (and `2024-07-16 11:51` in PHP, so hourly steps didn't match) | `2024-07-16 11:00` in both |
| `day` | `2024-07-16` | `2024-07-16` |
| `week` | `YEARWEEK()`, e.g. `202429` | The date the week starts on, e.g. `2024-07-14` |
| `month` | `2024, 07` | `2024-07` |
| `year` | `2024` | `2024` |

This changes the `default` formatter's output (the raw results) and anything that read the `week` or `month` columns of `results()`. The other formatters, the charts and the exports format the steps for display and don't change.

If you overrode `getSql()` on `TimeSeriesModes` usage or `getDateSqlForTimeMode()` in a stat, note that `TimeSeriesModes::getSql($field, $driver)` now takes the database driver.

Steps are counted from the start of the first step to the start of the last one, so a range that starts mid-week now includes the whole of both weeks it touches.

## Dates in requests

A `date_to` (or `compare_date_to`) without a time now covers the whole day: `2024-07-07` means up to `2024-07-07 23:59:59`, not midnight at the start of it. Likewise a `date_from` without a time starts at midnight. Dates with a time are used as given. As a result, the previous period of a custom range is now the same number of whole days before it.

`ExactDateRange::getPreviousDateRange()` now ends one second before the range starts and has the same length, ignoring microseconds.

The requests also accept:

- `date_range=custom` with `date_from` and `date_to`;
- `compare_to=previous` or `compare_to=custom` with `compare_date_from` and `compare_date_to`. `compare=1` and `compare_date_range` still work.

## Enums

| v1 | v2 |
| --- | --- |
| `implements IsEnum`, `use NativeEnumsTrait` | `use HasLabels` |
| `TimeSeriesModes::getLabels()` returned Hourly, Day, Week, Month, Year | Hour, Day, Week, Month, Year, translated |
| `PresetDateRanges` labels from the value | Explicit, translated labels ("Last 7 days") |
| `CategoricalModes` had no labels | "Categories with data", "All categories", "Specific categories" |
| — | `options()` on each enum: `[['value' => ..., 'label' => ...], ...]` |
| — | `TimeSeriesModes::startOfStep()`, `displayDate()`, `toIsoDate()`, `chartInterval()` |

## Filter values

v1 skipped a filter whose value was falsy, so `['user' => 0]` returned everything. v2 skips only missing values (`null`, `''`, `[]`); `0`, `'0'` and `false` are applied.

## Category search

The categories endpoint and the default search of model category providers now use `whereLike()`, so search is case-insensitive on PostgreSQL too. The endpoint also accepts `search` besides `filter[search]`, and `per_page=-1` for a single page of up to 100.

## Exports

Export file names keep their shape: `{App Name} {Stat name} {20240701-20240707} [{comparison}].csv`. The app name now comes from `config('app.name')` instead of the `app_name` setting. The `$filters` argument of `exportStats()` and `exportCategoricalStats()` is now merged with the request's `filters[...]` and the values of filters with form fields; v1 used only the argument for time series exports.

## Class names that moved or went away

| v1 | v2 |
| --- | --- |
| `Javaabu\Stats\Views\Components\TimeSeries`, `Categorical`, `Component` | Removed; see Views |
| `Javaabu\Helpers\Http\Controllers\Controller` as the base of the controllers | `Illuminate\Routing\Controller` |
| `Illuminate\Testing\Exceptions\InvalidArgumentException` from `ExactDateRange` | `InvalidArgumentException` |
| `MySQLRefreshDatabase` in the package's tests | Removed; the tests run on any database in `DB_CONNECTION` |

## Checklist

1. Update PHP, Laravel and javaabu/forms; remove javaabu/helpers, javaabu/settings and maatwebsite/excel from your app if nothing else uses them.
2. Replace the route helpers with `Route::stats()` and `Route::statsApi()`.
3. Delete published v1 views; set `stats.layout`.
4. Replace `<x-stats::time-series>` and `<x-stats::categorical>` with a model page or charts.
5. Sync permissions.
6. Check code that reads `week` or `month` keys from `results()` or the `default` format.
7. Replace `Excel::fake()` in tests of exports.
