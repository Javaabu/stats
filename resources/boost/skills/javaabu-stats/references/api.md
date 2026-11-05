# javaabu/stats API

## Stat base classes

| Class | You implement |
| --- | --- |
| `Repositories\TimeSeries\CountStatsRepository` | `query()`, `getTable()`, `getAggregateFieldName()`; optional `getDateFieldName()` (default `created_at`), `getName()`, `allowedFilters()`, `canView()` |
| `Repositories\TimeSeries\SumStatsRepository` | the same plus `getFieldToSum()` |
| `Repositories\TimeSeries\AggregateStatsRepository` | the same plus `getAggregateSql()` (`'avg(amount) as average'`) |
| `Repositories\TimeSeries\SignupsRepository`, `LoginsRepository` | `userModelClass()` |
| `Repositories\Categorical\CountCategoricalStatsRepository` | `query()`, `categoryProvider()`, `getCategoryField()`, `getDateField()`, `getAggregateFieldName()` |
| `Repositories\Categorical\SumCategoricalStatsRepository` | the same plus `getFieldToSum()` |

Using a stat directly: `TimeSeriesStats::createFromMetric('payments_count', PresetDateRanges::THIS_YEAR, ['user' => 1])` then `->results(TimeSeriesModes::MONTH)`, `->total()`, `->format('chartjs', TimeSeriesModes::DAY)`.

## Filters

| Filter | Value from | Applies |
| --- | --- | --- |
| `StatsFilter::exact('user', 'user_id')` | `filters[user]` | `where user_id = ?` |
| `StatsFilter::scope('island')` | `filters[island]` | `->island($value)` |
| `StatsFilter::closure('status', fn (Builder $query, $value) => ...)` | `filters[status]` | the closure |
| `StatsFilter::from(SelectFilter::make('status')->options(...))` | `status` (its own query keys), or `filters[status]` | the javaabu/filters filter; its fields appear in the stats form |

Names that can't be used for `StatsFilter::from()`: `metric`, `with`, `mode`, `variant`, `format`, `filters`, `categorical_values`, `date_range`, `date_from`, `date_to`, `compare`, `compare_to`, `compare_date_range`, `compare_date_from`, `compare_date_to`, `page`, `per_page`, `search`.

## Charts (`Javaabu\Stats\Charts`)

Every chart: `make(?string $metric = null, ?string $label = null)`, `title()`, `description()`, `id()`, `range(DateRange|string)`, `filters(array)`, `valueFormat('number'|'integer'|'compact'|'percent'|'currency')`, `height(int)`, `cache(int|bool, minutes: ?int)`, `withoutCache()`, `data()`, `refresh()`, `isWarm()`, `toArray()`, `render()`; also `Htmlable` and `JsonSerializable`.

| Chart | Also | Onu component |
| --- | --- | --- |
| `TimeSeriesChart` | `stat($metric, $label, $filters)`, `stats([...])`, `mode()`, `compare(true|DateRange)`, `periods([...])`, `line()`, `area()`, `bar()`, `stacked()`, `brush()`, `positiveIsGood(false)` | `TimeSeriesChart` |
| `CategoryChart` | `stat()`, `stats()`, `categoryMode()`, `categories([ids])`, `top(n)`, `compare()`, `bar()`, `line()`, `area()`, `horizontal()`, `stacked()`, `positiveIsGood()` | `CategoryChart` |
| `PieChart` | `stat()`, `categoryMode()`, `categories()`, `top(n)` (rest as "Other"), `donut()`, `centerLabel($value, $label)` / `centerLabel(false)`, `keepEmpty()` | `PieChart` |
| `Sparkline` | `mode()`, `area()`, `color('var(--chart-2)')` | `Sparkline` |
| `StatTile` | `label()`, `compare()`, `sparkline(mode)`, `icon()`, `href()`, `footer()`, `deltaContext()`, `positiveIsGood()` | `StatTile` |

`Stats::timeSeries()`, `Stats::category()`, `Stats::pie()`, `Stats::sparkline()`, `Stats::tile()` are shortcuts.

## Blade components

`<x-stats::chart :chart>` (any chart or its array; `:rtl` to force a direction), `<x-stats::time-series-chart>`, `<x-stats::category-chart>`, `<x-stats::pie-chart>`, `<x-stats::sparkline>`, `<x-stats::stat-tile :tile>`, `<x-stats::stat-tiles :columns="2|3|4">`. They require the `stats::charts` stylesheet through javaabu/manifest.

## React (`@javaabu/stats`)

`StatsChart`, `StatsTile`, `StatsPage`, `StatsExportButton`, `TimeSeriesStatsPage`, `CategoricalStatsPage`; types `StatsChartData`, `TimeSeriesChartData`, `CategoryChartData`, `PieChartData`, `SparklineData`, `StatTileData`, `StatsPageProps`. Inertia pages: `stats::TimeSeries/Index`, `stats::Categorical/Index`.

## Cache

`Stats::forget('metric' | [...])`, `Stats::flush()`, `Stats::warm(Chart|iterable|Closure)`, `Stats::warmNow()`, `php artisan stats:warm [--queue] [--flush]`, `Javaabu\Stats\Jobs\WarmStatsCache`, `Javaabu\Stats\Concerns\InvalidatesStats` (`$invalidates_stats`). Config: `stats.cache.enabled`, `store`, `prefix`, `ttl.current`, `ttl.past`, `pages`, `warm.connection`, `warm.queue`.

## Routes

| Macro | Options | Routes |
| --- | --- | --- |
| `Route::stats()` | `prefix` (`stats`), `name` (`stats`), `guard`, `middleware` (default `auth` or `auth:{guard}`), `time_series`, `categorical`, `controllers` | `{name}.time-series.index` (GET), `.time-series.export` (POST CSV), `.categorical.index`, `.categorical.export`, `.categorical.categories` |
| `Route::statsApi()` | the same, name `api.stats` | `{name}.time-series.index`, `.categorical.index`, `.categorical.categories` (JSON) |

Request parameters: `metric`, `with[]`, `mode`, `date_range` (preset or `custom`), `date_from`, `date_to`, `compare_to` (`previous`/`custom`), `compare` (bool), `compare_date_range`, `compare_date_from`, `compare_date_to`, `format`, `filters[...]`, filter fields, `categorical_values[]`, `variant`.

## Authorization

`view_stats` (group "Stats"), `StatsPolicy` (`view`, `export`) for `TimeSeriesStatsRepository` and `CategoricalStatsRepository`; `$stat->canView($user)` asks the gate. Config: `stats.policy`, `stats.register_permissions`.

## Config keys

`time_series_stats`, `categorical_stats`, `default_stats`, `user_model`, `week_starts_on_sunday`, `date_locale`, `date_formats`, `default_time_series_mode`, `default_categorical_mode`, `default_date_range`, `categorical_items_per_page`, `cache.*`, `views`, `layout`, `layout_section`, `rtl_locales`, `controllers.{time_series,categorical,time_series_api,categorical_api,categories}`, `policy`, `register_permissions`.
