---
title: Categorical stats
sidebar_position: 2
---

A categorical stat groups records by a category and counts or sums each group: payments by status, applications by island, revenue by customer. The categories and their labels come from a [category provider](./category-providers.md): a model, an enum, an array or your own class.

# Define a stat

Extend a base class in `Javaabu\Stats\Repositories\Categorical`. Or generate it: `php artisan stats:categorical PaymentsByIsland Payment Island island_id` (see [Artisan commands](../advanced-usage/artisan-commands.md)).

## Count

```php
<?php

namespace App\Stats\Categorical;

use App\Models\Island;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\CountCategoricalStatsRepository;

class PaymentsByIsland extends CountCategoricalStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->where('status', 'paid');
    }

    public function categoryProvider(): mixed
    {
        return Island::class;
    }

    public function getCategoryField(): string
    {
        return 'payments.island_id';
    }

    public function getCategoryFieldAlias(): string
    {
        return 'island';
    }

    public function getDateField(): string
    {
        return 'payments.paid_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'payments';
    }

    public function getName(): string
    {
        return __('Payments by island');
    }
}
```

## Sum

Extend `SumCategoricalStatsRepository` and return the column from `getFieldToSum()`:

```php
<?php

namespace App\Stats\Categorical;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\SumCategoricalStatsRepository;

class PaymentAmountsByStatus extends SumCategoricalStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function categoryProvider(): mixed
    {
        return PaymentStatus::class;
    }

    public function getCategoryField(): string
    {
        return 'payments.status';
    }

    public function getCategoryFieldAlias(): string
    {
        return 'status';
    }

    public function getDateField(): string
    {
        return 'payments.created_at';
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

Extend `AbstractCategoricalStatsRepository` and return the aggregate SQL, aliased as the aggregate field name:

```php
public function getAggregateSql(): string
{
    return 'avg(payments.amount) as ' . $this->getAggregateFieldName();
}
```

## What to implement

| Method | Required | What |
| --- | --- | --- |
| `query(): Builder` | Yes | The base query. Joins, scopes and `where`s are fine |
| `categoryProvider(): mixed` | Yes | Where the categories come from: a model class, model or query, an enum class, an array or collection, or a `CategoryProvider`. See [Category providers](./category-providers.md) |
| `getCategoryField(): string` | Yes | The column whose values are the category ids (`payments.island_id`). It can be a trusted SQL expression, such as `COALESCE(payments.island_id, 0)` or a `CASE`; it is used to select, group and order. Never build it from user input |
| `getDateField(): string` | Yes, in practice | The qualified date column the date range applies to. Default: `created_at`, which is ambiguous once the query has a join |
| `getAggregateFieldName(): string` | Yes | The name of the value in results. Also the default label, headlined and translated |
| `getFieldToSum(): string` | `SumCategoricalStatsRepository` | The column to sum |
| `getAggregateSql(): string` | `AbstractCategoricalStatsRepository` | The aggregate expression |
| `getCategoryFieldAlias(): string` | No | The name of the category id in results and formatted output. Default: `category` |
| `getCategoryNameFieldAlias(): string` | No | The name of the category label in formatted output. Default: `{category field alias}_name` |
| `getCategoryTitle(): string`, `getCategoryIdTitle(): string` | No | The CSV column titles. Default: the provider's (`Island`, `Island ID`) |
| `getName(): string` | No | The stat's name. Default: the class name, headlined, without `Repository` and (for counts) `Count` |
| `getAggregateFieldLabel(): string` | No | The label of the value. Default: the aggregate field name, headlined and translated |
| `allowedFilters(): array` | No | See [Filters](./filters.md) |
| `canView(?Authorizable $user = null): bool` | No | Who may see the stat. Default: the stats policy's `view` (`view_stats`) |

Categorical stats run a plain `GROUP BY`, so they work on any database Laravel supports.

# Register it

```php
// config/stats.php
'categorical_stats' => [
    'payments_by_island' => App\Stats\Categorical\PaymentsByIsland::class,
    'payment_amounts_by_status' => App\Stats\Categorical\PaymentAmountsByStatus::class,
],
```

or in a service provider's `boot()`:

```php
use App\Stats\Categorical\PaymentsByIsland;
use Javaabu\Stats\CategoricalStats;

CategoricalStats::register([
    'payments_by_island' => PaymentsByIsland::class,
]);
```

`CategoricalStats` has the same lookups as `TimeSeriesStats`: `statsMap()`, `getClassNameForMetric()`, `getMetricForStat()`, `getMetricName()`, `getMetricNames()`, `allowedMetrics()`, `metricsThatAllowFilters()` and `canViewAny()`. See [Time series stats](./time-series-stats.md#register-it).

# Which categories

A categorical mode (`Javaabu\Stats\Enums\CategoricalModes`) decides which categories are listed:

| Mode | Value | Lists |
| --- | --- | --- |
| `NON_EMPTY` | `non_empty` | Categories with data in the period, or in the comparison period. The default (`stats.default_categorical_mode`) |
| `ALL` | `all` | Every category the provider has, with `0` for those without data |
| `SPECIFIC` | `specific` | Only the category ids you give |

# Use a stat in PHP

```php
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;

$stat = CategoricalStats::createFromMetric('payments_by_island', PresetDateRanges::THIS_MONTH);

$rows = $stat->results();                      // a Collection of rows: ['island' => 12, 'payments' => 40]
$values = $stat->resultsToArray($rows);        // [12 => 40, 15 => 7], values keyed by category id
$names = $stat->resolveCategoryNames(CategoricalModes::SPECIFIC, [12, 15]); // [12 => 'Male', 15 => 'Hulhumale']

$compare = CategoricalStats::createFromMetric('payments_by_island', PresetDateRanges::LAST_MONTH);

$formatted = $stat->format('combined', $compare, CategoricalModes::ALL);
// [['island' => 12, 'island_name' => 'Male', 'payments' => 40, 'compare_payments' => 31], ...]

$formatted = $stat->format('default', mode: CategoricalModes::SPECIFIC, categorical_values: [12]);
```

`format(string $format, ?CategoricalStatsRepository $compare = null, CategoricalModes $mode = CategoricalModes::NON_EMPTY, array $categorical_values = [])` takes a formatter's name: `default`, `combined` or `chartjs`, or one you register. See [Formatters](../advanced-usage/formatters.md).

Date ranges, filters and the date methods work as for [time series stats](./time-series-stats.md#date-ranges). `getCategoryProvider()` returns the resolved `CategoryProvider`.

To draw a categorical stat, use `CategoryChart` or `PieChart`; see [Charts](./charts.md).
