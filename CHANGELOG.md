# Changelog

All notable changes will be documented in this file

## 2.0.0 - unreleased

See [Upgrading from v1](docs/upgrading.md) for the breaking changes.

### Added

- Chart builders: `TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline` and `StatTile`, with several stats or several periods on one plot and comparisons with the previous period.
- Blade chart components (`<x-stats::chart>`, `time-series-chart`, `category-chart`, `pie-chart`, `sparkline`, `stat-tile`, `stat-tiles`) drawn as accessible SVG with Onu's tokens.
- React (`@javaabu/stats` through javaabu/manifest): `StatsChart`, `StatsTile`, `StatsPage` and the Inertia pages `stats::TimeSeries/Index` and `stats::Categorical/Index`, on Onu's chart components.
- Cached charts with TTLs for current and past periods, `Stats::forget()`, `Stats::flush()`, the `InvalidatesStats` model trait, `Stats::warm()`, `php artisan stats:warm` and the `WarmStatsCache` job.
- Stats forms defined with javaabu/stencil; `StatsFilter::from()` uses javaabu/filters filters as stats filters with fields in the form.
- `Route::stats()` and `Route::statsApi()` with prefix, name, guard, middleware and controllers; controllers in `config('stats.controllers')`; a `filters()` hook for pages of one model.
- `view_stats` registered with javaabu/permissions and an overridable `StatsPolicy`.
- Date grouping on MySQL, MariaDB, PostgreSQL and SQLite.
- `compare_to` and `date_range=custom` request parameters, `with[]` for plotting other stats.
- Dhivehi translations and right-to-left charts.
- Docs for people and agents, `llms.txt`, a Laravel Boost guideline and the `javaabu-stats` skill.

### Changed

- PHP 8.4+, Laravel 12 or 13, javaabu/forms v2.
- CSV exports without maatwebsite/excel; file names use `config('app.name')`.
- Week keys are the date the week starts on, month keys `YYYY-MM`; hourly keys now match the SQL.
- A plain `date_to` covers the whole day.
- Built-in stats use `config('stats.user_model')` or the default guard's user model.
- Category search is case-insensitive on every database.

### Removed

- The material-admin views, `<x-stats::time-series>` and `<x-stats::categorical>`, and the `framework`, `default_layout`, `scripts_stack`, `time_series_stats_view` and `categorical_stats_view` config keys.
- The dependencies on javaabu/helpers, javaabu/settings and maatwebsite/excel.

## 1.0.0 - 202X-XX-XX

- initial release
