# Common mistakes

- **Writing your own date grouping.** Don't `selectRaw('DATE(created_at) ...')` or `YEARWEEK()`: extend a stat class; the package groups dates for MySQL, MariaDB, PostgreSQL and SQLite and fills empty steps with zeros.
- **Unregistered metrics.** Charts take a registered metric (`'payments_count'`) or the stat's class name. A typo throws `StatsException::unknownMetric`.
- **Building charts in JavaScript.** Don't fetch the JSON API and draw with Chart.js. Build the chart in PHP and pass it to `<x-stats::chart>` or `<StatsChart>`.
- **Charts that never refresh in tests.** Charts are cached. In tests that create data after building a chart, use `->withoutCache()` or `config()->set('stats.cache.enabled', false)`.
- **Dashboard and warmed charts that differ.** The cache key is the chart's spec (metrics, range, mode, filters, options, locale). Build them in one place (a method or a closure registered with `Stats::warm()`) so the dashboard reads what was warmed.
- **Forgetting to invalidate.** Add `InvalidatesStats` to the models stats are computed from, or call `Stats::forget('metric')` after bulk imports, or the dashboard shows old numbers until the TTL ends.
- **`filters[...]` for a form filter.** Filters made with `StatsFilter::from()` read their own query keys (`status=paid`); `filters[...]` is for fixed filters such as the model of a model-specific page.
- **Naming a form filter after a stats parameter** (`mode`, `metric`, `date_from`, …): it throws. Pick another name and set `->column()` on the javaabu/filters filter.
- **Assuming the `web` guard.** Pass `guard` to `Route::stats()` for admin portals; type users as `Authorizable`/`Model`, not `App\Models\User`.
- **Checking `view_stats` yourself.** Use `$stat->canView($user)` or the gate (`can('view', $stat)`), so an app's policy override applies.
- **Type-hinting `Carbon`.** A stat's dates are `CarbonImmutable` in apps that call `Date::use(CarbonImmutable::class)` (Laravel's starter kits do). Type-hint `CarbonInterface` in your own `DateRange` classes and stat overrides, and write `$date = $date->addDay()`, never a bare `$date->addDay();`.
- **Left/right CSS in published views.** Use logical utilities (`ms-`, `pe-`, `start-`); the charts mirror in Dhivehi.

## Upgrading from v1

- Replace `TimeSeriesStats::registerRoutes()` / `registerApiRoute()` and `CategoricalStats::registerRoutes()` / `registerApiRoutes()` with `Route::stats()` and `Route::statsApi()` (the old ones still work, deprecated).
- The material-admin views and `<x-stats::time-series>` / `<x-stats::categorical>` are gone. Use the pages, a model page (extend the controller's `filters()`), or charts.
- Week keys are now the week's start date (`2024-07-14`), month keys `2024-07`; code reading the raw `default` format must change.
- Exports are CSV without maatwebsite/excel: `(new TimeSeriesStatsExport($mode, $stats))->download('file.csv')`; tests assert the download instead of `Excel::fake()`.
- A plain `date_to` covers the whole day.
- Date methods type-hint `CarbonInterface` instead of `Carbon`; overrides of `setDateFrom()`, `setDateTo()`, `getMaxDate()` and `getMinDate()` must widen their parameters.
- See `docs/upgrading.md` for the full list.
