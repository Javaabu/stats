---
title: Stats pages
sidebar_position: 7
---

`Route::stats()` gives you two pages where people explore every registered stat: pick a stat, a date range and filters, see the chart, and download it as CSV. One page is for time series stats, the other for categorical stats. They render in Blade or Inertia React.

# Routes

```php
// routes/web.php
Route::stats();
```

| Route | Name | What |
| --- | --- | --- |
| `GET /stats/time-series` | `stats.time-series.index` | The time series page |
| `POST /stats/time-series` | `stats.time-series.export` | Its CSV download |
| `GET /stats/categorical` | `stats.categorical.index` | The categorical page |
| `POST /stats/categorical` | `stats.categorical.export` | Its CSV download |
| `GET /stats/categorical/categories` | `stats.categorical.categories` | The category search behind the "Choose categories" field |

`Route::stats()` takes options:

| Option | Default | What |
| --- | --- | --- |
| `prefix` | `stats` | The URL prefix |
| `name` | `stats` | The route name prefix (`admin.stats` gives `admin.stats.time-series.index`) |
| `guard` | `null` (the default guard) | The guard users log in with. Permissions are checked for its user |
| `middleware` | `['auth']`, or `["auth:{guard}"]` with a guard | The route middleware. Replaces the default, so include an `auth` middleware |
| `time_series` | `true` | Register the time series page |
| `categorical` | `true` | Register the categorical page and the category search |
| `controllers` | From `stats.controllers` | Controller classes for this set of routes: `time_series`, `categorical`, `categories`. See [Overriding controllers and views](../advanced-usage/overriding-controllers-and-views.md) |

```php
Route::stats([
    'guard' => 'web_admin',
    'prefix' => 'admin/stats',
    'name' => 'admin.stats',
    'categorical' => false,
]);
```

The routes need sessions and CSRF protection for the download, so register them in the `web` middleware group (`routes/web.php` is).

Link to a page with the stat already chosen:

```php
route('stats.time-series.index', ['metric' => 'payments_count', 'mode' => 'month', 'date_range' => 'this_year']);
```

# What the pages do

- Users must be logged in, and see only the stats they may view (`view_stats` by default). With none, the page answers `403`.
- The form is a plain `GET` form, so every chart has a URL you can bookmark or share.
- With no parameters, the page shows the first stat for the default date range (`stats.default_date_range`) and mode.
- Invalid parameters don't redirect: the page shows the form with its errors and no chart.
- The chart has the same options as the [chart builders](./charts.md). The pages show live data; set `stats.cache.pages` to `true` to cache them like other charts.
- A "Download CSV" button posts the same parameters to the export route.

## The time series page

The chart is a `TimeSeriesChart` of the chosen stat, plus any stats in "Plot with", compared with another period when asked.

## The categorical page

The chart is a `CategoryChart` (bars, horizontal bars or a line), or a donut `PieChart` when "Pie" is chosen for a single stat without a comparison.

# Query parameters

Both pages:

| Parameter | Values | What |
| --- | --- | --- |
| `metric` | A metric the user can see | The stat. Default: the first one |
| `with[]` | Metrics | Other stats to plot on the same chart |
| `date_range` | A preset value (`last_30_days`), or `custom` | Default: `stats.default_date_range` |
| `date_from`, `date_to` | Dates | A custom range, when `date_range` is `custom` (or missing). A date without a time covers the whole day |
| `compare_to` | `previous` or `custom` | Compare with the previous period, or with `compare_date_from` to `compare_date_to` |
| `compare_date_from`, `compare_date_to` | Dates | The custom comparison period |
| `compare_date_range` | A preset value | Compare with a preset period |
| `compare` | `1` | Compare with the previous period (the v1 parameter) |
| `filters[name]` | Any | Fixed filters. See [Filters](./filters.md#in-a-url) |
| `{filter}` | Any | The values of `StatsFilter::from()` filters, by their own parameters (`status=paid`) |

The time series page:

| Parameter | Values | Default |
| --- | --- | --- |
| `mode` | `hour`, `day`, `week`, `month`, `year` | `stats.default_time_series_mode` |
| `variant` | `line`, `area`, `bar` | `line` |

The categorical page:

| Parameter | Values | Default |
| --- | --- | --- |
| `mode` | `non_empty`, `all`, `specific` | `stats.default_categorical_mode` |
| `categorical_values[]` | Category ids | Required when `mode` is `specific` |
| `variant` | `bar`, `horizontal`, `line`, `pie` | `bar` |

```text
/stats/time-series?metric=payments_count&with[]=refunds_count&mode=week&date_range=this_year&compare_to=previous&status=paid
/stats/categorical?metric=payments_by_island&mode=specific&categorical_values[]=12&categorical_values[]=15&date_range=custom&date_from=2024-07-01&date_to=2024-07-31
```

# The form

The forms are [javaabu/stencil](https://github.com/Javaabu/stencil) forms, `Javaabu\Stats\Forms\TimeSeriesStatsForm` and `CategoricalStatsForm`, rendered by javaabu/forms in Blade and by `<StencilForm>` in React. They are `GET` forms with a "Generate" button, in two columns on medium screens and four on large ones.

| Field | Page | What |
| --- | --- | --- |
| Stat (`metric`) | Both | A searchable select of the stats the user can see |
| Group by (`mode`) | Time series | Hour, day, week, month or year |
| Categories (`mode`) | Categorical | Categories with data, all categories, or specific categories |
| Date range (`date_range`) | Both | The presets, and "Custom range" |
| Compare to (`compare_to`) | Both | No comparison, the previous period, or a custom period |
| From, To (`date_from`, `date_to`) | Both | Shown for a custom range |
| Compare from, Compare to date (`compare_date_from`, `compare_date_to`) | Both | Shown for a custom comparison |
| Choose categories (`categorical_values`) | Categorical | A searchable multiple select of the chosen stat's categories, loaded from the category search. Shown for specific categories |
| Plot with (`with`) | Both | Other stats to show on the same chart |
| Chart (`variant`) | Both | The chart type |
| Filter fields | Both | The fields of each `StatsFilter::from()` filter, shown while a stat that has it is chosen |
| `filters[...]` | Both | Hidden fields that keep the fixed filters |

Use the forms in your own pages:

```php
use Javaabu\Stats\Forms\TimeSeriesStatsForm;
use Javaabu\Stats\TimeSeriesStats;

$form = TimeSeriesStatsForm::make()
    ->with([
        'metrics' => TimeSeriesStats::getMetricNames([], $request->user()),   // the stats to offer
        'filters' => [],                                                     // fixed filters
    ])
    ->model($request->query())
    ->action(route('reports.payments'));
```

`CategoricalStatsForm` also takes `categories_url` in `with()`: the URL of the category search (`route('stats.categorical.categories')`).

# Blade

With `'views' => 'blade'` (the default), the pages are `stats::time-series.index` and `stats::categorical.index`. They extend `config('stats.layout')` (`layouts.app`) and fill `config('stats.layout_section')` (`content`), and include `stats::partials.page`.

They get these variables, if you [publish and change them](../advanced-usage/overriding-controllers-and-views.md):

| Variable | What |
| --- | --- |
| `$form` | The stencil form (echo it: `{{ $form }}`) |
| `$chart` | The chart's array, or `null` when the input is invalid |
| `$export` | `url`, `params` (the page's parameters) and `token` (`null` in Blade; the view adds `@csrf`), or `null` with no chart |
| `$strings` | `title`, `export`, `empty`, `invalid`, translated |
| `$layout`, `$layout_section` | From the config |

The pages need no JavaScript of their own. javaabu/forms' components bring theirs (for the searchable selects and date pickers) through javaabu/manifest.

# Inertia React

With `'views' => 'inertia'`, the pages are `stats::TimeSeries/Index` and `stats::Categorical/Index`. See [Installation](../installation-and-setup.md#inertia-react) for the Vite and `resolvePage()` setup. The props are the variables above, with:

- `form` as stencil's JSON schema,
- `export.token` as the CSRF token, for the download form,
- `errors` with the first error of each invalid field.

The pieces are exported as `@javaabu/stats`, to build your own page:

| Export | What |
| --- | --- |
| `TimeSeriesStatsPage`, `CategoricalStatsPage` | The two pages |
| `StatsPage` | Their body: the title, `<StencilForm>`, the chart and the download button, or a message. Props: `StatsPageProps` and `page` |
| `StatsExportButton` | The download form. Props: `download` (the `export` prop) and `label` |
| `StatsPageProps`, `StatsExport`, `StatsPageStrings` | The prop types |

```tsx
// resources/vendor/stats/js/Pages/TimeSeries/Index.tsx
import type { ReactNode } from "react"
import { StatsPage } from "@javaabu/stats/components/StatsPage"
import type { StatsPageProps } from "@javaabu/stats/types"
import ReportsLayout from "@/layouts/reports"

export default function Index(props: StatsPageProps) {
  return <StatsPage page="time-series" {...props} />
}

Index.layout = (page: ReactNode) => <ReportsLayout>{page}</ReportsLayout>
```

A file at `resources/vendor/stats/js/Pages/...` replaces the package's page; see [Overriding controllers and views](../advanced-usage/overriding-controllers-and-views.md#the-react-pages).

# CSV export

The "Download CSV" button posts the page's parameters to the export route, which answers with a CSV file:

- named `{App name} {Stat name} {20240701-20240731} [{comparison dates}].csv`,
- starting with a UTF-8 byte order mark, so spreadsheet apps show Thaana correctly,
- with a few `#` comment lines at the top: the stat's name and the dates,
- of the chosen stat only (not the "Plot with" stats).

Time series: a row per step, with the step, the comparison period's dates when compared (a row per step of each period), and the value.

```text
# ----------------------------------------
# Payments
# 20240703-20240704
# ----------------------------------------

Day,Payments
2024-07-03,3
2024-07-04,2
```

Categorical: a row per category, with the category id, the name, and the value, or a value per period when compared.

Invalid input redirects back with the errors, like any form request. The download needs the same permission as the page.

To offer the download from your own controller, use the traits the package's controllers use:

```php
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReportsController extends Controller
{
    use ExportsTimeSeriesStats;

    public function export(TimeSeriesStatsRequest $request): StreamedResponse
    {
        return $this->exportStats($request, filters: ['island' => 12], export_title: 'Male');
    }
}
```

`exportStats()` and `exportCategoricalStats()` (from `ExportsCategoricalStats`, with a `CategoricalStatsRequest`) take the request, extra fixed filters, and a prefix for the file name (default: the app's name). They answer `403` when the user can't see the stat with those filters.

# Stats for one model

A page of one model's stats (an island's payments, a customer's orders) is the stats page with a fixed filter. Extend the controller and return the filter from `filters()`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Island;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;

class IslandStatsController extends TimeSeriesStatsController
{
    protected function filters(Request $request): array
    {
        $island = Island::query()->findOrFail($request->route('island'));

        Gate::authorize('view', $island);

        return ['island' => $island->id];
    }
}
```

`filters()` is called for the page and for the download. Its filters are added to every stat, the page offers only the stats that allow them, and they're sent back as hidden `filters[...]` fields, so people can't remove them.

Register the routes with the model's id in the prefix. The pages pass the current route's parameters on to their download and category-search URLs, so nothing else is needed:

```php
// routes/web.php
use App\Http\Controllers\Admin\IslandStatsController;

Route::stats([
    'prefix' => 'islands/{island}/stats',
    'name' => 'islands.stats',
    'categorical' => false,
    'controllers' => ['time_series' => IslandStatsController::class],
]);
```

Link to it with `route('islands.stats.time-series.index', $island)`. For a categorical page, extend `CategoricalStatsController` the same way and pass it as `controllers.categorical`.

The stats need the filter:

```php
public function allowedFilters(): array
{
    return [
        StatsFilter::exact('island', 'payments.island_id'),
    ];
}
```

For a few fixed charts of one model, on its own page, you don't need the stats pages: use the [chart builders](./charts.md) with `->filters(['island' => $island->id])`.

# The v1 route helpers

`TimeSeriesStats::registerRoutes()`, `TimeSeriesStats::registerApiRoute()`, `CategoricalStats::registerRoutes()` and `CategoricalStats::registerApiRoutes()` still work, with their v1 URLs, names and middleware, and are deprecated. Use `Route::stats()` and `Route::statsApi()`. See [Upgrading](../upgrading.md).
