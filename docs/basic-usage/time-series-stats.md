---
title: Time series stats
sidebar_position: 1
---

A time series stat is a value that changes over time: payments per day, sign-ups per week, revenue per month. The package groups it by one of five modes (`Javaabu\Stats\Enums\TimeSeriesModes`):

| Mode | Value | Step key in results |
| --- | --- | --- |
| `HOUR` | `hour` | `2024-07-04 09:00` |
| `DAY` | `day` | `2024-07-04` |
| `WEEK` | `week` | The date the week starts on: `2024-06-30` |
| `MONTH` | `month` | `2024-07` |
| `YEAR` | `year` | `2024` |

# Define a stat

Extend one of the base classes in `Javaabu\Stats\Repositories\TimeSeries`. Each needs a query, the table and a name for the value. Or generate the class: `php artisan stats:time-series PaymentsCount Payment` (see [Artisan commands](../advanced-usage/artisan-commands.md)).

## Count

`CountStatsRepository` counts rows:

```php
<?php

namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCount extends CountStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->where('status', 'paid');
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getDateFieldName(): string
    {
        return 'paid_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'payments';
    }

    public function getName(): string
    {
        return __('ފައިސާ ދެއްކުން');
    }
}
```

## Sum

`SumStatsRepository` adds up a column. Return it from `getFieldToSum()`:

```php
<?php

namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\SumStatsRepository;

class PaymentAmountsSum extends SumStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->where('status', 'paid');
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getDateFieldName(): string
    {
        return 'paid_at';
    }

    public function getFieldToSum(): string
    {
        return 'payments.amount';
    }

    public function getAggregateFieldName(): string
    {
        return 'amount';
    }
}
```

## Any other aggregate

`AggregateStatsRepository` takes the aggregate SQL. Alias it as the aggregate field name:

```php
<?php

namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\AggregateStatsRepository;

class AveragePaymentAmount extends AggregateStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->where('status', 'paid');
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getAggregateSql(): string
    {
        return 'avg(payments.amount) as ' . $this->getAggregateFieldName();
    }

    public function getAggregateFieldName(): string
    {
        return 'average_amount';
    }
}
```

The SQL comes from your code, never from the request. It runs grouped by the step (`GROUP BY`) and once more without grouping for the total.

## What to implement

| Method | Required | What |
| --- | --- | --- |
| `query(): Builder` | Yes | The base query. Scopes, joins and `where`s are fine; the package adds the date range, the filters, the select and the grouping |
| `getTable(): string` | Yes | The main table, used to qualify the date column |
| `getAggregateFieldName(): string` | Yes | The name of the value in results (`payments`). Also the default label, headlined and translated (`Payments`) |
| `getFieldToSum(): string` | `SumStatsRepository` | The column to sum |
| `getAggregateSql(): string` | `AggregateStatsRepository` | The aggregate expression, aliased as the aggregate field name |
| `getDateFieldName(): string` | No | The date column. Default: `created_at` |
| `getDateField(): string` | No | The qualified date column. Default: `{table}.{date field name}`. Override it for a joined table or an expression |
| `getName(): string` | No | The stat's name in selects, titles and CSV files. Default: the class name, headlined, without `Repository` and (for counts) `Count`: `PaymentsCount` is "Payments" |
| `getAggregateFieldLabel(): string` | No | The label of the value in tables and CSV headings. Default: the aggregate field name, headlined and translated |
| `allowedFilters(): array` | No | Filters the stat accepts. See [Filters](./filters.md) |
| `canView(?Authorizable $user = null): bool` | No | Who may see the stat. Default: the `view` ability of the stats policy (`view_stats`). See [Permissions and policies](../advanced-usage/permissions-and-policies.md) |
| `getDateSqlForTimeMode(TimeSeriesModes $mode): string` | No | The SQL that groups the date column. See [Databases](../advanced-usage/databases.md) |

Restrict who sees one stat:

```php
use Illuminate\Contracts\Auth\Access\Authorizable;

public function canView(?Authorizable $user = null): bool
{
    return $user?->can('view_payment_stats') ?? false;
}
```

# Register it

Give each stat a metric name. The metric is what pages, charts, the API and the cache use.

```php
// config/stats.php
'time_series_stats' => [
    'payments_count' => App\Stats\TimeSeries\PaymentsCount::class,
    'payment_amounts' => App\Stats\TimeSeries\PaymentAmountsSum::class,
],
```

or in a service provider's `boot()`:

```php
use App\Stats\TimeSeries\PaymentAmountsSum;
use App\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\TimeSeriesStats;

TimeSeriesStats::register([
    'payments_count' => PaymentsCount::class,
    'payment_amounts' => PaymentAmountsSum::class,
]);
```

`register()` adds to what is registered. Pass `false` as the second argument to replace everything instead: `TimeSeriesStats::register([...], false)`. The stats pages list the stats in the order they were registered.

Look stats up with:

| Method | Returns |
| --- | --- |
| `TimeSeriesStats::statsMap()` | Classes keyed by metric |
| `TimeSeriesStats::getClassNameForMetric('payments_count')` | The class |
| `TimeSeriesStats::getMetricForStat(PaymentsCount::class)` | The metric |
| `TimeSeriesStats::getMetricName('payments_count')` | The stat's name |
| `TimeSeriesStats::getMetricNames(array $filters = [], ?Authorizable $user = null)` | Names keyed by metric, for the stats the user can see that allow the filters |
| `TimeSeriesStats::allowedMetrics(array $filters = [], ?Authorizable $user = null)` | The same, as a list of metrics |
| `TimeSeriesStats::metricsThatAllowFilters(array $filters, ?Authorizable $user = null, StatListReturnType $return_type = StatListReturnType::METRIC_AND_NAME)` | Either of the above, or classes keyed by metric with `StatListReturnType::METRIC_AND_CLASS` |
| `TimeSeriesStats::canViewAny(?Authorizable $user = null)` | Whether the user can see any stat |

`$stat->metric()` gives a stat instance's metric.

# Built-in stats

When `stats.default_stats` is on (the default), two stats are registered for your users:

| Metric | Class | What |
| --- | --- | --- |
| `user_signups` | `UserSignupsRepository` | Users created, by `created_at` |
| `user_logins` | `UserLoginsRepository` | Logins, from [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog): activities with the description `login` caused by a user. Only when activitylog is installed |

The user model is `config('stats.user_model')`, or the model of the default guard's user provider when that is `null`. Nothing is registered when neither gives a class.

```php
// config/stats.php
'default_stats' => true,
'user_model' => App\Models\Admin::class,
```

To leave them out, set `'default_stats' => false`, or call `TimeSeriesStats::excludeDefaultStats()` in a service provider's `register()`. To register them at a point of your choosing (to change their order), exclude them and call `TimeSeriesStats::registerDefaultStats()` in `boot()` after your own.

`user_logins` accepts a filter named after the user model's morph class (`user` with a morph map, otherwise the class name), for one user's logins.

## Sign-ups and logins of other models

Extend `SignupsRepository` or `LoginsRepository` and return the model:

```php
<?php

namespace App\Stats\TimeSeries;

use App\Models\Customer;
use Javaabu\Stats\Repositories\TimeSeries\LoginsRepository;

class CustomerLogins extends LoginsRepository
{
    public function userModelClass(): string
    {
        return Customer::class;
    }
}
```

For other activity log events, extend `ActivityLogStatsRepository` (a count of activity log rows) and return the description:

```php
<?php

namespace App\Stats\TimeSeries;

use Javaabu\Stats\Repositories\TimeSeries\ActivityLogStatsRepository;

class PasswordResets extends ActivityLogStatsRepository
{
    public function eventDescription(): string
    {
        return 'password_reset';
    }

    public function getAggregateFieldName(): string
    {
        return 'resets';
    }
}
```

# Use a stat in PHP

Make a stat for a date range, with optional filters:

```php
use App\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;

$stat = TimeSeriesStats::createFromMetric('payments_count', PresetDateRanges::LAST_30_DAYS);
$stat = TimeSeriesStats::createFromMetric('payments_count', new ExactDateRange('2024-07-01', '2024-07-31 23:59:59'), ['island' => 5]); // a stat that allows an island filter
$stat = new PaymentsCount(PresetDateRanges::THIS_YEAR);       // the date range defaults to THIS_YEAR

$total = $stat->total();                                     // 1250 (int or float)
$rows = $stat->results(TimeSeriesModes::DAY);                // a Collection of rows: ['payments' => 3, 'day' => '2024-07-03']
$filled = $stat->format('combined', TimeSeriesModes::DAY);   // every day, with 0 for days without data
```

`results()` only has steps with data. To get every step, use a formatter (see [Formatters](../advanced-usage/formatters.md)) or a chart (see [Charts](./charts.md)), which fill the gaps with zero.

Filters that the stat doesn't allow throw `Javaabu\Stats\Exceptions\InvalidFiltersException`.

## Date ranges

A date range is a `PresetDateRanges` case or an `ExactDateRange`. Both implement `Javaabu\Stats\Contracts\DateRange`.

| Preset | Value | Covers |
| --- | --- | --- |
| `TODAY` | `today` | Today |
| `YESTERDAY` | `yesterday` | Yesterday |
| `THIS_WEEK` | `this_week` | This week, starting on Sunday or Monday (`stats.week_starts_on_sunday`) |
| `LAST_WEEK` | `last_week` | Last week |
| `THIS_MONTH` | `this_month` | This month |
| `LAST_MONTH` | `last_month` | Last month |
| `THIS_YEAR` | `this_year` | This year |
| `LAST_YEAR` | `last_year` | Last year |
| `LAST_7_DAYS` | `last_7_days` | Today and the 6 days before |
| `LAST_14_DAYS` | `last_14_days` | Today and the 13 days before |
| `LAST_30_DAYS` | `last_30_days` | Today and the 29 days before |
| `LAST_5_YEARS` | `last_5_years` | This year and the 4 before |
| `LAST_10_YEARS` | `last_10_years` | This year and the 9 before |
| `LIFETIME` | `lifetime` | From the stat's first record to its last |

```php
$range = PresetDateRanges::from('last_30_days');
$range->getDateFrom();              // CarbonInterface
$range->getDateTo();
$range->getLabel();                 // "Last 30 days", translated
$range->getPreviousDateRange();     // the 30 days before, as an ExactDateRange
PresetDateRanges::getLabels();      // labels keyed by value, for selects

$range = new ExactDateRange('2024-07-01', '2024-07-31 23:59:59');
$range->getPreviousDateRange();     // the same length, ending the second before 1 July
```

An `ExactDateRange` takes anything Carbon parses. A date without a time starts at midnight, so give the end date a time (or `Carbon::parse(...)->endOfDay()`) to include the whole day. Its end can't be before its start.

The dates are made with Laravel's `Date` factory, so they are `CarbonImmutable` in an app that calls `Date::use(CarbonImmutable::class)` (as Laravel's starter kits do) and `Carbon` otherwise. Type-hint `CarbonInterface`, and use the return value of methods like `startOfDay()` or `addDay()` (`$date = $date->addDay()`) instead of relying on them changing the date in place, so your code works with both.

## The stat's dates

| Method | What |
| --- | --- |
| `getDateRange()` | The `DateRange` it was made with |
| `getDateFrom()`, `getDateTo()` | The resolved start and end |
| `setDateRange(DateRange $range)`, `setDateFrom($date)`, `setDateTo($date)` | Change them |
| `formattedDateRange(string $format = 'YYYY-MM-DD HH:mm', string $separator = ' - ')` | `2024-07-01 00:00 - 2024-07-31 23:59` |
| `interval(TimeSeriesModes $mode)` | The number of steps after the first |
| `getMinDate()`, `getMaxDate()` | The earliest and latest date in the query |

## The stat's queries

| Method | What |
| --- | --- |
| `query()` | Your base query |
| `filteredQuery()` | With the filters and the date range |
| `filteredQueryWithoutDateFilters()` | With the filters only |
| `hour()`, `day()`, `week()`, `month()`, `year()` | The grouped query for a mode |
| `groupedQuery(TimeSeriesModes $mode)` | The same, for any mode |
