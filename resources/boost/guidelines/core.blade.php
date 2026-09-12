## Javaabu Stats

This package provides time-series statistics for Laravel applications, supporting aggregate count and sum queries with filters, formatters, date ranges, and CSV export.

### Architecture

Stats are managed through a central static registry `TimeSeriesStats` and follow a repository pattern:

- `CountStatsRepository` — counts rows grouped by time period. Implement `query(): Builder`, `getTable(): string`, `getAggregateFieldName(): string`.
- `SumStatsRepository` — sums a numeric field grouped by time period. Same methods as count, plus `getFieldToSum(): string`.

Stat classes live in `App\Stats\TimeSeries\` and are registered via `TimeSeriesStats::register()` in a service provider's `boot()` method with snake_case metric names.

### Artisan Generator

Generate and auto-register a stat class: `php artisan stats:time-series {Name} {Model} --type={count|sum}`.

### Filters

Define allowed filters by returning `Filter` objects from `allowedFilters()` using the `StatsFilter` factory:

- `StatsFilter::exact('name', 'column')` — matches a column value exactly
- `StatsFilter::scope('name', 'scopeMethod')` — calls an Eloquent query scope
- `StatsFilter::closure('name', fn)` — applies arbitrary query logic

### Formatters

Five built-in formatters: `default`, `chartjs`, `sparkline`, `flot`, `combined`. Register custom formatters via `TimeSeriesStats::registerFormatters()`.

### Routes

- `TimeSeriesStats::registerApiRoute()` — registers a GET endpoint returning JSON stats data
- `TimeSeriesStats::registerRoutes()` — registers GET (web view) and POST (CSV export) endpoints

### Authorization

Override `canView(?Authorizable $user)` on a stat repository to control access. The default checks for `view_stats` permission. The `stats.view-time-series` middleware alias is auto-registered.

### Configuration

Publish with `php artisan vendor:publish --tag=stats-config`. Other publish tags: `stats-views`, `stats-stubs`. Key options in `config/stats.php`: `week_starts_on_sunday`, `date_locale`, `date_formats`, `default_time_series_mode`, `default_date_range`, `framework`, `default_layout`.
