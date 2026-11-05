---
title: Filters
sidebar_position: 4
---

Filters let one stat answer narrower questions: payments of one customer, applications from one island, payments with a status. A stat lists the filters it accepts in `allowedFilters()`; you pass values by the filter's name.

```php
use Javaabu\Filters\SelectFilter;
use Javaabu\Stats\Filters\StatsFilter;

public function allowedFilters(): array
{
    return [
        StatsFilter::exact('island', 'payments.island_id'),
        StatsFilter::scope('paid_by'),
        StatsFilter::from(SelectFilter::make('status')->label('ހާލަތު')->options(PaymentStatus::class)),
    ];
}
```

Time series and categorical stats take filters the same way.

# Filter types

## exact

`StatsFilter::exact(string $name, string $internal_name = '')` adds `where {internal_name} = {value}`. The internal name is the column; it defaults to the filter's name.

```php
StatsFilter::exact('island', 'payments.island_id');   // ?filters[island]=12
StatsFilter::exact('user_id');                        // the column is user_id
```

## scope

`StatsFilter::scope(string $name, string $internal_name = '')` calls a query scope with the value. The scope defaults to the filter's name in camel case: `paid_by` calls `paidBy($value)`, that is `scopePaidBy()`.

```php
StatsFilter::scope('paid_by');                // $query->paidBy($value)
StatsFilter::scope('customer', 'ownedBy');    // $query->ownedBy($value)
```

## closure

`StatsFilter::closure(string $name, Closure $callback)` calls your closure. It gets `$query`, `$value` and `$stat` by name (and anything else from the container), and returns the query:

```php
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\InteractsWithFilters;

StatsFilter::closure('atoll', function (Builder $query, $value, InteractsWithFilters $stat) {
    return $query->whereHas('island', fn (Builder $query) => $query->where('atoll_id', $value));
});
```

## javaabu/filters

`StatsFilter::from(\Javaabu\Filters\Filter $filter)` uses a [javaabu/filters](https://github.com/Javaabu/filters) filter, the same ones your index pages use. It filters the query the way the filter does, and it adds the filter's fields to the [stats pages](./stats-pages.md)' form, shown while a stat that has the filter is selected.

```php
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\SelectFilter;

public function allowedFilters(): array
{
    return [
        StatsFilter::from(SelectFilter::make('status')->label('ހާލަތު')->options(PaymentStatus::class)),
        StatsFilter::from(SelectFilter::make('island_id')->label('ރަށް')->relationship('island', 'name')->searchable()),
        StatsFilter::from(DateRangeFilter::make('due_at')->label(__('Due'))),
    ];
}
```

It returns a `Javaabu\Stats\Filters\ModelFilter`. Values pass through the filter's own cleaning first, so `' paid '` becomes `paid` and empty values are ignored.

# Pass filter values

By name, wherever a stat is made:

```php
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\TimeSeriesStats;

$stat = TimeSeriesStats::createFromMetric('payments_count', PresetDateRanges::THIS_MONTH, ['island' => 12, 'status' => 'paid']);

$chart = TimeSeriesChart::make('payments_count')->filters(['island' => 12]);
```

A filter the stat doesn't allow throws `Javaabu\Stats\Exceptions\InvalidFiltersException`, which names the filters it does allow. A filter whose value is missing (`null`, `''` or `[]`) isn't applied; `0`, `'0'` and `false` are values and are applied.

# In a URL

The stats pages and the JSON API read filter values from the query string in two ways:

| Form | For | Example |
| --- | --- | --- |
| `filters[name]=value` | Fixed filters: any filter type, by name. The pages send them back as hidden fields, so they stay fixed while people change the rest | `?filters[island]=12` |
| `name=value` | Filters made with `StatsFilter::from()`, by their own query parameters, as on an index page | `?status=paid`, `?due_at_from=2024-07-01&due_at_to=2024-07-31` |

A value in `filters[...]` wins over the same filter's flat parameters.

Only stats that allow every fixed filter are offered. `?filters[island]=12` lists the stats with an `island` filter, and leaves out the rest. The same goes for your own lists:

```php
TimeSeriesStats::getMetricNames(['island' => 12], auth()->user());     // names keyed by metric
TimeSeriesStats::allowedMetrics(['island'], auth()->user());           // metrics; filter names are enough
```

## Reserved names

The stats forms use these query parameters, so a `StatsFilter::from()` filter can't be named after one, or read one (`ModelFilter::RESERVED`):

`metric`, `with`, `mode`, `variant`, `format`, `filters`, `categorical_values`, `date_range`, `date_from`, `date_to`, `compare`, `compare_to`, `compare_date_range`, `compare_date_from`, `compare_date_to`, `page`, `per_page`, `search`

Making one throws a `Javaabu\Stats\Exceptions\StatsException`. Watch for defaults: `TextFilter::make()` is named `search`, and `DateRangeFilter::make('date')` reads `date_from` and `date_to`. Name them: `TextFilter::make('reference')`, `DateRangeFilter::make('paid_on')`.

# Your own filter type

Implement `Javaabu\Stats\Contracts\Filter`:

```php
<?php

namespace App\Stats\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\Filter;
use Javaabu\Stats\Contracts\InteractsWithFilters;

class MinimumAmountFilter implements Filter
{
    public function getName(): string
    {
        return 'min_amount';
    }

    public function apply(Builder $query, $value, InteractsWithFilters $stat): Builder
    {
        return $query->where('amount', '>=', $value);
    }
}
```

To give it fields in the stats form, also implement `Javaabu\Stats\Contracts\HasFields`: `getFields()` returns stencil fields named after its query parameters, `getQueryKeys()` lists those parameters, and `resolveValue(array $input)` turns the query string into the filter's value, or `null` when it doesn't apply. Extending `Javaabu\Stats\Filters\AbstractFilter` gives you the name and internal name.

For a stats page of one model (every stat filtered to one customer, say), see [Stats pages](./stats-pages.md#stats-for-one-model).
