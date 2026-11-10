---
title: Overriding controllers and views
sidebar_position: 4
---

# The controllers

Extend a controller and point the config at it, or pass it to one set of routes:

```php
// config/stats.php
'controllers' => [
    'time_series' => App\Http\Controllers\StatsController::class,
    'categorical' => Javaabu\Stats\Http\Controllers\CategoricalStatsController::class,
    'time_series_api' => Javaabu\Stats\Http\Controllers\Api\TimeSeriesStatsApiController::class,
    'categorical_api' => Javaabu\Stats\Http\Controllers\Api\CategoricalStatsApiController::class,
    'categories' => Javaabu\Stats\Http\Controllers\Api\CategoryProviderApiController::class,
],
```

```php
// routes/web.php
Route::stats(['controllers' => ['time_series' => App\Http\Controllers\StatsController::class]]);

// routes/api.php
Route::statsApi(['controllers' => ['time_series_api' => App\Http\Controllers\Api\StatsController::class]]);
```

`Route::stats()` takes `time_series`, `categorical` and `categories`; `Route::statsApi()` takes `time_series_api`, `categorical_api` and `categories`.

## The page controllers

`TimeSeriesStatsController` and `CategoricalStatsController` have these methods to override:

| Method | What |
| --- | --- |
| `filters(Request $request): array` | Fixed filters for every stat on the page and in the download. Empty by default. See [Stats for one model](../basic-usage/stats-pages.md#stats-for-one-model) |
| `index(Request $request)` | The page |
| `chart($request)` | The chart for valid input: a `TimeSeriesChart`, or a `CategoryChart` / `PieChart` |
| `viewData(Request $request, $form, ?$chart, $stats_request, array $metrics): array` | What the Blade view or the Inertia page gets |
| `export($request)` | The CSV download |

Change the chart, for example to always show the brush and format money:

```php
<?php

namespace App\Http\Controllers;

use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;

class StatsController extends TimeSeriesStatsController
{
    protected function chart(TimeSeriesStatsRequest $request): TimeSeriesChart
    {
        $chart = parent::chart($request)->brush();

        if (str_ends_with($request->metric(), '_amounts')) {
            $chart->valueFormat(Chart::FORMAT_CURRENCY);
        }

        return $chart;
    }
}
```

The controllers find their own routes by name: they link to `{name}.time-series.export`, `{name}.categorical.export` and `{name}.categorical.categories`, where `{name}` is the prefix of the current route's name. Register controllers through `Route::stats()`, or keep that naming if you register the routes yourself.

## The API controllers

`TimeSeriesStatsApiController` and `CategoricalStatsApiController` have `index()` (see [JSON API](./json-api.md)); `CategoryProviderApiController` has `index()` for the category search.

# The Blade views

```bash
php artisan vendor:publish --tag=stats-views
```

They are copied to `resources/views/vendor/stats`:

| View | What |
| --- | --- |
| `time-series/index.blade.php`, `categorical/index.blade.php` | The pages: they extend the layout and include the partial |
| `partials/page.blade.php` | The page body: title, form, chart, download button, messages |
| `components/charts/cartesian.blade.php` | Time series and category charts |
| `components/charts/pie.blade.php` | Pie and donut charts |
| `components/charts/sparkline.blade.php` | Sparklines |
| `components/charts/stat-tile.blade.php` | Stat tiles |
| `components/charts/header.blade.php`, `table.blade.php`, `delta.blade.php` | The chart title, the "View as table" disclosure and the change badge |
| `components/stat-tiles.blade.php` | The tile grid (`<x-stats::stat-tiles>`) |

Copy only the files you change; Laravel uses the package's for the rest. The page variables are listed in [Stats pages](../basic-usage/stats-pages.md#blade). To only change the layout, set `stats.layout` and `stats.layout_section` instead.

The chart styles are in `resources/css/charts.css` (the `stats::charts` asset). They use `stats-` classes and CSS variables (`--stats-foreground`, `--stats-muted`, `--stats-border`, `--stats-card`, `--stats-radius`, `--stats-positive`, `--stats-negative`, `--stats-previous`) that default to Onu's tokens. Override the variables in your CSS to restyle charts, or replace the file through javaabu/manifest by putting your copy at `resources/vendor/stats/css/charts.css` (your app must build with manifest's Vite plugin).

# The React pages

Copy `vendor/javaabu/stats/resources/js/Pages/TimeSeries/Index.tsx` to `resources/vendor/stats/js/Pages/TimeSeries/Index.tsx` (or `Categorical/Index.tsx`) and change it: manifest's `resolvePage()` and Vite plugin use the app's copy first. manifest copies it for you:

```bash
php artisan manifest:override stats js/Pages/TimeSeries/Index.tsx
```

Any other file under `resources/js` can be overridden the same way, such as `components/StatsChart.tsx`. Restart `npm run dev` after adding an override.

Build on the package's pieces, imported as `@javaabu/stats` or by path (`@javaabu/stats/components/StatsPage`). See [Stats pages](../basic-usage/stats-pages.md#inertia-react) for an example.

# The translations

```bash
php artisan vendor:publish --tag=stats-translations
```

They are copied to `lang/vendor/stats`. Your app's own `lang/dv.json` also wins for any key it has. See [Dhivehi and RTL](./dhivehi-and-rtl.md).

# The generator stubs

```bash
php artisan vendor:publish --tag=stats-stubs
```

They are copied to `stubs/vendor/stats`, and `stats:time-series` and `stats:categorical` use your copies. See [Artisan commands](./artisan-commands.md).
