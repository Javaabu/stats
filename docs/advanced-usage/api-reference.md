---
title: API reference
sidebar_position: 10
---

Everything is in the `Javaabu\Stats` namespace unless a full name is given.

# `TimeSeriesStats` and `CategoricalStats`

Static registries of stats and formatters. Both have:

| Method | Returns |
| --- | --- |
| `register(array $stats, bool $merge = true)` | Register stats as metric => class. `false` replaces the registered ones |
| `statsMap()` | Classes keyed by metric |
| `getClassNameForMetric(string $metric)` | The class (the metric itself when not registered) |
| `getMetricForStat(string $stat)` | The metric of a class |
| `createFromMetric(string $metric, DateRange $date_range = PresetDateRanges::THIS_YEAR, array $filters = [])` | A stat instance |
| `getMetricName(string $metric)` | The stat's name |
| `getMetricNames(array\|string $filters = [], ?Authorizable $user = null)` | Names keyed by metric, of the stats the user can see that allow the filters |
| `allowedMetrics(array\|string $filters = [], ?Authorizable $user = null)` | The same, as a list of metrics |
| `metricsThatAllowFilters(array\|string $filters, ?Authorizable $user = null, StatListReturnType $return_type = StatListReturnType::METRIC_AND_NAME)` | Either, or classes keyed by metric |
| `canViewAny(?Authorizable $user = null)` | Whether the user can see any stat |
| `defaultMode()` | `TimeSeriesModes` or `CategoricalModes`, from the config |
| `defaultDateRange()` | The default `PresetDateRanges` |
| `registerFormatters(array $formatters, bool $merge = true)` | Register formatters as name => class |
| `formattersMap()`, `allowedFormats()` | Formatter classes by name, and the names |
| `createFromFormat(string $format)` | A formatter instance |
| `getClassNameForFormat(string $name)`, `getNameForFormatter(string $formatter)` | Look formatters up |

`TimeSeriesStats` also has:

| Method | Returns |
| --- | --- |
| `registerDefaultStats()` | Register `user_signups` and `user_logins` |
| `excludeDefaultStats()`, `shouldExcludeDefaultStats()` | Keep the built-in stats out |
| `dateLocale()` | The Carbon locale used for weeks (`en_GB@Stats`) |
| `firstDayOfWeek()` | `Carbon::SUNDAY` or `Carbon::MONDAY` |
| `weekMode()` | The MySQL `WEEK()` mode matching the first day (v1) |
| `registerRoutes()`, `registerApiRoute()` | Deprecated v1 routes. Use `Route::stats()` and `Route::statsApi()` |

`CategoricalStats` also has `categoricalItemsPerPage()` and the deprecated `registerRoutes()` and `registerApiRoutes()`.

# `Facades\Stats` (`StatsManager`)

| Method | Returns |
| --- | --- |
| `timeSeries(?string $metric = null, ?string $label = null)` | `TimeSeriesChart` |
| `category(?string $metric = null, ?string $label = null)` | `CategoryChart` |
| `pie(?string $metric = null, ?string $label = null)` | `PieChart` |
| `sparkline(?string $metric = null, ?string $label = null)` | `Sparkline` |
| `tile(?string $metric = null, ?string $label = null)` | `StatTile` |
| `cache()` | The `StatsCache` |
| `forget(string\|array $metrics = '*')` | Clear the cached charts of the metrics (all for `*` or `[]`) |
| `flush()` | Clear every cached chart |
| `warm(Chart\|iterable\|Closure $charts)` | Register charts to warm |
| `warmables()` | The registered charts |
| `warmNow(?Closure $after = null)` | Compute and cache them; returns how many |
| `clearWarmables()` | Forget the registered charts |

# Charts

## `Charts\Chart` (every chart)

| Method | What |
| --- | --- |
| `title(?string)`, `description(?string)`, `id(?string)` | Texts and the HTML id |
| `range(DateRange\|string)`, `filters(array)` | The date range and fixed filters |
| `valueFormat(string)`, `height(?int)` | Display |
| `cache(int\|bool $seconds = true, ?int $minutes = null)`, `withoutCache()` | Caching |
| `data()`, `refresh()`, `isWarm()`, `isCached()` | The computed data and its cache |
| `toArray()`, `jsonSerialize()`, `render()`, `toHtml()` | Output |
| `type()`, `metrics()` | What it is |
| `getTitle()`, `getId()`, `getRange()`, `getFilters()`, `getValueFormat()`, `getHeight()` | Getters |

Constants: `FORMAT_NUMBER`, `FORMAT_INTEGER`, `FORMAT_COMPACT`, `FORMAT_PERCENT`, `FORMAT_CURRENCY`.

From `Charts\Concerns\HasStats` (every chart): `stat(string $metric, ?string $label = null, array $filters = [])`, `stats(array $metrics)`, `getSeries()`.

From `Charts\Concerns\HasCategories` (`CategoryChart`, `PieChart`): `categoryMode(CategoricalModes|string)`, `getCategoryMode()`, `categories(array $ids)`, `top(?int $limit)`, `makeStat(string $metric, DateRange $range, array $filters = [])`.

## The builders

All have `static make(?string $metric = null, ?string $label = null)`.

| Class | Methods | Constants |
| --- | --- | --- |
| `Charts\TimeSeriesChart` | `mode()`, `getMode()`, `periods(array)`, `compare(DateRange\|string\|bool = true)`, `getCompareRange()`, `positiveIsGood(bool = true)`, `variant(string)`, `line()`, `area()`, `bar()`, `stacked(bool = true)`, `brush(bool = true)`, `makeStat(string $metric, DateRange $range, array $filters = [])` | `LINE`, `AREA`, `BAR` |
| `Charts\CategoryChart` | `compare()`, `getCompareRange()`, `positiveIsGood()`, `variant()`, `bar()`, `line()`, `area()`, `horizontal(bool = true)`, `stacked(bool = true)` | `BAR`, `LINE`, `AREA` |
| `Charts\PieChart` | `donut(bool = true)`, `centerLabel(string\|false\|null $value, ?string $label = null)`, `keepEmpty(bool = true)` | |
| `Charts\Sparkline` | `mode()`, `getMode()`, `area(bool = true)`, `color(?string)` | |
| `Charts\StatTile` | `label(?string)`, `compare()`, `getCompareRange()`, `sparkline(TimeSeriesModes\|string\|bool = true)`, `icon(?string)`, `href(?string)`, `positiveIsGood()`, `deltaContext(?string)`, `footer(?string)` | |

See [Charts](../basic-usage/charts.md) for what each does.

# Stats

## Contracts

`Contracts\TimeSeriesStatsRepository` and `Contracts\CategoricalStatsRepository` extend `Contracts\InteractsWithDateRange` and `Contracts\InteractsWithFilters`.

| `InteractsWithDateRange` | `InteractsWithFilters` |
| --- | --- |
| `getDateFrom()`, `getDateTo()`, `getDateRange()` | `allowedFilters()` |
| `setDateFrom()`, `setDateTo()`, `setDateRange(DateRange)` | `getFilters()`, `getFilter(string $filter, $default = null)`, `setFilters(array)` |
| `formattedDateRange(string $format, string $separator)` | `isAllowedFilter(string)` |
| `getDateField()`, `getMinDate()`, `getMaxDate()` | `ensureAllFiltersAllowed(array $filters, bool $throw_on_fail = false)` |
| `interval(TimeSeriesModes)` | `applyFilters(Builder)`, `filteredQuery()` |
| `applyDateFilters(Builder)`, `filteredQueryWithoutDateFilters()` | |

Dates are `CarbonInterface`: `getDateFrom()`, `getDateTo()`, `getMinDate()` and `getMaxDate()` return the app's date class (`Carbon`, or `CarbonImmutable` after `Date::use(CarbonImmutable::class)`), and `setDateFrom()` and `setDateTo()` take a string or any `DateTimeInterface`.

`TimeSeriesStatsRepository` adds `query()`, `hour()`, `day()`, `week()`, `month()`, `year()`, `results(TimeSeriesModes)`, `total()`, `format(string $format, TimeSeriesModes $mode = TimeSeriesModes::DAY)`, `getCurrentMode()`, `getAggregateFieldName()`, `getAggregateFieldLabel()`, `getName()`, `canView(?Authorizable)` and `metric()`.

`CategoricalStatsRepository` adds `query()`, `categoryProvider()`, `getCategoryProvider()`, `getCategoryField()`, `getCategoryFieldAlias()`, `getCategoryNameFieldAlias()`, `getCategoryTitle()`, `getCategoryIdTitle()`, `getAggregateFieldName()`, `getAggregateFieldLabel()`, `getName()`, `canView()`, `metric()`, `results()`, `resultsToArray(Collection)`, `resolveCategoryNames(CategoricalModes $mode, array $categorical_values = [])` and `format(string $format, ?CategoricalStatsRepository $compare = null, CategoricalModes $mode = CategoricalModes::NON_EMPTY, array $categorical_values = [])`.

## Base classes

| Class | You implement |
| --- | --- |
| `Repositories\TimeSeries\AbstractTimeSeriesStatsRepository` | Everything about the queries |
| `Repositories\TimeSeries\AggregateStatsRepository` | `query()`, `getTable()`, `getAggregateSql()`, `getAggregateFieldName()`. Has `getDateFieldName()`, `getDateField()`, `getDateSqlForTimeMode(TimeSeriesModes)`, `groupedQuery(TimeSeriesModes)` |
| `Repositories\TimeSeries\CountStatsRepository` | `query()`, `getTable()`, `getAggregateFieldName()` |
| `Repositories\TimeSeries\SumStatsRepository` | The same, and `getFieldToSum()` |
| `Repositories\TimeSeries\SignupsRepository` | `userModelClass()` |
| `Repositories\TimeSeries\LoginsRepository` | `userModelClass()`. Needs spatie/laravel-activitylog |
| `Repositories\TimeSeries\ActivityLogStatsRepository` | `eventDescription()`, `getAggregateFieldName()` |
| `Repositories\TimeSeries\UserSignupsRepository`, `UserLoginsRepository` | Nothing: the built-in stats. `UserSignupsRepository::defaultUserModel()` gives their model |
| `Repositories\Categorical\AbstractCategoricalStatsRepository` | `query()`, `categoryProvider()`, `getCategoryField()`, `getDateField()`, `getAggregateSql()`, `getAggregateFieldName()`. Has `groupByCategory()` |
| `Repositories\Categorical\CountCategoricalStatsRepository` | The same, without `getAggregateSql()` |
| `Repositories\Categorical\SumCategoricalStatsRepository` | The same, and `getFieldToSum()` |

The traits `Concerns\HasDateRange` and `Concerns\HasFilters` implement the two contracts above.

# Filters

| Class | What |
| --- | --- |
| `Filters\StatsFilter` | `exact(string $name, string $internal_name = '')`, `scope(string $name, string $internal_name = '')`, `closure(string $name, Closure $callback)`, `from(\Javaabu\Filters\Filter $filter)` |
| `Filters\ExactFilter`, `Filters\ScopeFilter`, `Filters\ClosureFilter` | The filters those make. `getName()`, `getInternalName()`, `apply(Builder $query, $value, InteractsWithFilters $stat)` |
| `Filters\AbstractFilter` | A base for filters with a name and an internal name |
| `Filters\ModelFilter` | A javaabu/filters filter: `getFilter()`, `getFields()`, `getQueryKeys()`, `resolveValue(array $input)`. `RESERVED` lists the names it can't use |
| `Contracts\Filter` | `getName()`, `apply()` |
| `Contracts\HasFields` | `getFields()`, `getQueryKeys()`, `resolveValue(array $input)` |

# Category providers

| Class | What |
| --- | --- |
| `Contracts\CategoryProvider` | `getCategoricalStatsItems(?array $ids = null)`, `searchCategoricalStatsItems(?string $search = null, int $per_page = 15)`, `sortCategoricalStatsItemsBy(string $field, string $direction = 'asc')`, `sortCategoricalStatsItemsUsing(Closure)`, `getCategoricalStatsCategoryTitle()`, `getCategoricalStatsCategoryIdTitle()` |
| `CategoryProviders\EloquentCategoryProvider` | `new EloquentCategoryProvider(Builder\|Model\|string $source, string $id_field = '', string $label_field = '', string $sort_field = '', string $sort_direction = 'asc', ?Closure $sort_query = null)` |
| `CategoryProviders\EnumCategoryProvider` | `new EnumCategoryProvider(string $enum, string $sort_field = '', string $sort_direction = 'asc', ?Closure $sort_callback = null)` |
| `CategoryProviders\ArrayCategoryProvider` | `new ArrayCategoryProvider(iterable $categories, string $sort_field = '', string $sort_direction = 'asc', ?Closure $sort_callback = null)` |
| `Concerns\IsCategoricalStatsProvider` | Makes a model a provider. Adds `getCategoricalStatsIdField()`, `getCategoricalStatsLabelField()`, `getCategoricalStatsSearchField()` |
| `Concerns\HasCategoricalStatsItems` | Makes an in-memory provider: implement `categoricalStatsItems(): iterable` |
| `Support\CategoryProviderFactory` | `make(mixed $provider)`: wraps a provider source |

# Enums

| Enum | Cases | Methods |
| --- | --- | --- |
| `Enums\TimeSeriesModes` | `HOUR`, `DAY`, `WEEK`, `MONTH`, `YEAR` | `getLabel()`, `getSql(string $date_field, string $driver = 'mysql')`, `interval(CarbonInterface, CarbonInterface)`, `startOfStep(CarbonInterface)`, `increment(CarbonInterface)`, `formatDate(CarbonInterface, bool $for_display = true)`, `displayDate(CarbonInterface, ?string $locale = null)`, `toIsoDate(CarbonInterface)`, `getDateFormat()`, `getInternalDateFormat()`, `chartInterval()` |
| `Enums\CategoricalModes` | `NON_EMPTY`, `ALL`, `SPECIFIC` | `getLabel()`, `getDescription()`, `resolveCategoryNames(...)` |
| `Enums\PresetDateRanges` | `TODAY`, `YESTERDAY`, `THIS_WEEK`, `LAST_WEEK`, `THIS_MONTH`, `LAST_MONTH`, `THIS_YEAR`, `LAST_YEAR`, `LAST_7_DAYS`, `LAST_14_DAYS`, `LAST_30_DAYS`, `LAST_5_YEARS`, `LAST_10_YEARS`, `LIFETIME` | `getDateFrom()`, `getDateTo()`, `getName()`, `getLabel()`, `getPreviousDateRange()` |
| `Enums\StatListReturnType` | `METRIC`, `METRIC_AND_NAME`, `METRIC_AND_CLASS` | |

`TimeSeriesModes`, `CategoricalModes` and `PresetDateRanges` have `getLabels()` (labels keyed by value) and `options()` (a list of `value` and `label`) from `Enums\Concerns\HasLabels`.

# Support

| Class | What |
| --- | --- |
| `Contracts\DateRange` | `getDateFrom()`, `getDateTo()`, `getName()`, `getPreviousDateRange()` |
| `Support\ExactDateRange` | `new ExactDateRange($date_from, $date_to)`. Its name is `custom` |
| `Support\TimeSeriesSteps` | `for(TimeSeriesStatsRepository $stats, TimeSeriesModes $mode)`: every step with `date`, `key`, `value`. `rangeLabel(DateRange $range, ?CarbonInterface $from = null, ?CarbonInterface $to = null)` |
| `Support\Numbers` | `format(int\|float\|null $value, string $format = 'number', ?string $locale = null)`, `tick()`, `compact()`, `currency()`, `currencySymbol()` |
| `Support\ExportFileName` | `make(string $title, $stats, $compare = null, string $prefix = '')` |

# Cache

| Class | What |
| --- | --- |
| `Cache\StatsCache` | `remember()`, `refresh()`, `has()`, `forget()`, `flush()`, `ttlFor()`, `key()`, `version()`, `enabled()`, `store()`, `prefix()`. `ALL` is `*`. See [Caching and warming](./caching-and-warming.md#the-cache-service) |
| `Concerns\InvalidatesStats` | Model trait. `$invalidates_stats`, `statsToInvalidate()` |
| `Jobs\WarmStatsCache` | Queued, unique for 600 seconds. Runs `Stats::warmNow()` |

# HTTP

| Class | What |
| --- | --- |
| `Http\Controllers\TimeSeriesStatsController`, `CategoricalStatsController` | `index()`, `export()`, and the hooks `filters()`, `chart()`, `viewData()` |
| `Http\Controllers\Api\TimeSeriesStatsApiController`, `CategoricalStatsApiController` | `index()` |
| `Http\Controllers\Api\CategoryProviderApiController` | `index()` |
| `Http\Controllers\Concerns\RendersStatsPage` | `views()`, `statsUser()`, `pageRequest()`, `shareErrors()`, `errorMessages()`, `attributeNames()`, `pageStrings()` |
| `Http\Requests\TimeSeriesStatsRequest`, `CategoricalStatsRequest` | `metric()`, `otherMetrics()`, `mode()`, `stats(?string $metric = null)`, `compareStats(?string $metric = null)`, `rules()`, `bodyParameters()`. Categorical: `categoricalValues()` |
| `Http\Requests\Concerns\ResolvesStats` | `statsUser()`, `fixedFilters()`, `filtersFor(InteractsWithFilters)`, `hasCustomDateRange()`, `dateRange(?DateRange $default = null)`, `compareDateRangeName()`, `compareDateRange(?DateRange $date_range = null)`. Constants `CUSTOM_RANGE` (`custom`), `COMPARE_PREVIOUS` (`previous`), `COMPARE_CUSTOM` (`custom`) |
| `Http\Middleware\AbortIfCannotViewAnyTimeSeriesStats`, `AbortIfCannotViewAnyCategoricalStats` | Aliased `stats.view-time-series` and `stats.view-categorical` |
| `Concerns\ExportsTimeSeriesStats` | `exportStats(TimeSeriesStatsRequest $request, array $filters = [], string $export_title = '')` |
| `Concerns\ExportsCategoricalStats` | `exportCategoricalStats(CategoricalStatsRequest $request, array $filters = [], string $export_title = '')` |
| `Routing\StatsRoutes` | `register(Router, array $options = [])`, `registerApi(Router, array $options = [])`, `guard(Request)`, `namePrefix(Request, string $default = 'stats')`. `GUARD_ACTION` |
| `Policies\StatsPolicy` | `view()`, `export()`. `VIEW` is `view_stats` |

Route macros: `Route::stats(array $options = [])` and `Route::statsApi(array $options = [])`. See [Stats pages](../basic-usage/stats-pages.md#routes) and [JSON API](./json-api.md#routes).

# Forms

| Class | What |
| --- | --- |
| `Forms\StatsForm` | The shared stencil form. Context: `metrics`, `filters` |
| `Forms\TimeSeriesStatsForm` | The time series form |
| `Forms\CategoricalStatsForm` | The categorical form. Context: `categories_url`. `PIE` is `pie` |
| `Forms\Fields\CategorySelect` | A searchable multiple select loaded over AJAX. `dependsOn(string\|Closure $parent_selector, string\|Closure $filter_field)` |

# Exports

| Class | What |
| --- | --- |
| `Exports\CsvExport` | `array()`, `headings()`, `map($row)`, `getReportTitle()`, `formattedDateRange()`, `preamble()`, `lines()`, `toCsv()`, `download(string $file_name)` |
| `Exports\TimeSeriesStatsExport` | `new TimeSeriesStatsExport(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null)`. Also `metric()` |
| `Exports\CategoricalStatsExport` | `new CategoricalStatsExport(CategoricalStatsRepository $stats, ?CategoricalStatsRepository $compare = null, CategoricalModes $mode = CategoricalModes::NON_EMPTY, array $categorical_values = [])` |

```php
$export = new TimeSeriesStatsExport(TimeSeriesModes::DAY, $stat);

$csv = $export->toCsv();
return $export->download('payments.csv');
```

# Views

| Class | What |
| --- | --- |
| `Views\ViewRenderer` | `render(string $page, array $data = [])`, `stack()`, `isInertia()`, `inertiaPage(string $page)`. `BLADE`, `INERTIA` |
| `Views\Components\Chart` | `<x-stats::chart :chart :rtl />` |
| `Views\Components\TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline` | The same component under other names |
| `Views\Components\StatTile` | `<x-stats::stat-tile :tile :chart :rtl />` |
| `stats::components.stat-tiles` | `<x-stats::stat-tiles :columns />` |

# Exceptions

| Class | Thrown when |
| --- | --- |
| `Exceptions\StatsException` | `unsupportedDriver()`: grouping a time series stat on another database. `unknownMetric()`: a chart's metric isn't registered. `reservedFilterName()`: a `StatsFilter::from()` filter uses a reserved name. `inertiaMissing()`: `stats.views` is `inertia` without inertia-laravel |
| `Exceptions\InvalidFiltersException` | A stat gets a filter it doesn't allow. `$unknown_filters`, `$allowed_filters` |

# JavaScript (`@javaabu/stats`)

| Export | What |
| --- | --- |
| `StatsChart` | `{ chart: StatsChartData, className?: string }` |
| `StatsTile` | `{ tile: StatTileData }` and Onu `StatTile` props except `label` and `value` |
| `StatsPage` | `StatsPageProps & { page: string }` |
| `StatsExportButton` | `{ download: StatsExport, label: string }` |
| `TimeSeriesStatsPage`, `CategoricalStatsPage` | The Inertia pages |
| Types | `StatsChartData`, `StatsChartBase`, `TimeSeriesChartData`, `CategoryChartData`, `PieChartData`, `SparklineData`, `StatTileData`, `StatsValueFormat`, `StatsSeriesConfig`, `StatsComparison`, `StatsExport`, `StatsPageStrings`, `StatsPageProps` |

Inertia pages: `stats::TimeSeries/Index` and `stats::Categorical/Index`.
