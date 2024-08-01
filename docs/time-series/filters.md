---
title: Using filters
sidebar_position: 4
---

# Defining allowed filters

You can add allowed filters to your stat class by overriding the `allowedFilters` method of your stat class. The `allowedFilters` method should return an array of `Javaabu\Stats\Contracts\Filter` objects.

```php
...
class PaymentsCountRepository extends AggregateStatsRepository
{
    public function allowedFilters(): array
    {
        return [
            new \Javaabu\Stats\Filters\ExactFilter('customer', 'payer_id'),
        ];
    }
...
```

You can also use the provided `StatsFilter` class to more easily define a filter.

```php
...
use Javaabu\Stats\Filters\StatsFilter;

class PaymentsCountRepository extends AggregateStatsRepository
{
    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('customer', 'payer_id'),
        ];
    }
...
```

In the above 2 examples, the `PaymentsCountRepository` will now accept a filter called `customer` which can be used to filter payment counts for a specific customer.

# Available Filters

The `StatsFilter` class has the following available filter methods.

## exact

Exact filters checks whether the given filter value matches exactly with the given field.

```php
$filter = StatsFilter::exact('customer', 'customer_id'),
```

The first argument is the name of the filter. The 2nd argument is the internal name for the field. If the 2nd argument is ommitted, the first argument is used as the internal name.

## scope

Scope filters calls the given query scope method with the given filter value.

```php
$filter = StatsFilter::scope('customer', 'search'),
```

The first argument is the name of the filter. The 2nd argument is the internal name for the scope. If the 2nd argument is ommitted, the first argument is used as the scope name.

## closure

Closure filters calls the given closure method with the given filter value.

```php
use \Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\InteractsWithFilters;

$filter = StatsFilter::closure('customer', function (Builder $query, $value, InteractsWithFilters $stat) {
            return $query->where('name', $value);
        }),
```

The closure will get access to 3 values, which is the `$query` builder instance, the filter `$value` and the `$stat` class.

# Getting stats that allow only a specific set of filters

For some use cases, you might want to retrieve all stats that allows a certain filter or set of filters. For example, you might want to display all stats that has a `customer` filter. To do this, you can call the `TimeSeriesStats::metricsThatAllowFilters` method.

```php
$customer_stats = \Javaabu\Stats\TimeSeriesStats::metricsThatAllowFilters(['customer']);
```

You can also pass a `user` object to this method to get stats that are visible to that user.

```php
$customer_stats = \Javaabu\Stats\TimeSeriesStats::metricsThatAllowFilters(['customer'], auth()->user());
```

By default, `metricsThatAllowFilters` will return an associative array of stat metric names and the stat display name. If you want to instead return either the stat class names or metric names only, you can set the return type using the 3rd argument.

```php
use Javaabu\Stats\Enums\StatListReturnType;
use \Javaabu\Stats\TimeSeriesStats;

$only_metric_names = TimeSeriesStats::metricsThatAllowFilters(['customer'], auth()->user(), StatListReturnType::METRIC);
$metric_name_and_class_name = TimeSeriesStats::metricsThatAllowFilters(['customer'], auth()->user(), StatListReturnType::METRIC_AND_CLASS);
```
