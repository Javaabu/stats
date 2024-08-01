---
title: Basic Concepts
sidebar_position: 2
---

Time series statistics are any statistic that varies over time. Using time series stats, you can track any numerical value that changes over time within a given time period. For example, this could be daily user signups, weekly sales, etc. Currently, this package allows viewing time series stats in the following modes:

- Hour
- Day
- Week 
- Month
- Year

# Instantiating a stat class

To use a Time Series Stat Repository, you need to make a new instance of the class by providing a date range to generate the stats for.
The data range should be either a `\Javaabu\Stats\Enums\PresetDateRanges` enum or an `\Javaabu\Stats\Support\ExactDateRange` object.

For preset date ranges, the package offers the following presets:
- `TODAY`
- `YESTERDAY`
- `THIS_WEEK`
- `LAST_WEEK`
- `THIS_MONTH`
- `LAST_MONTH`
- `THIS_YEAR`
- `LAST_YEAR`
- `LAST_7_DAYS`
- `LAST_14_DAYS`
- `LAST_30_DAYS`
- `LIFETIME`

For exact date ranges, you can provide your own start and end date:

```php
use \Javaabu\Stats\Support\ExactDateRange;

$date_range = new ExactDateRange('2024-07-30 12:32:00', '2024-08-02 13:42:00');
```

Once you've a date range, you can instantiate a new instance of the stat:

```php
use \Javaabu\Stats\Repositories\TimeSeries\UserLoginsRepository;
use \Javaabu\Stats\Enums\PresetDateRanges;

$stat = new UserLoginsRepository(PresetDateRanges::LAST_7_DAYS);
```

You can also instantiate a stat using the `TimeSeriesStats::createFromMetric()` method. When calling this method, you have to use the registered metric name for the stat.

```php
use \Javaabu\Stats\TimeSeriesStats;
use \Javaabu\Stats\Enums\PresetDateRanges;

$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
```

# Getting the results from a stat class

Once you have instantiated a stat class, you can use the stat class to get the results in any of the available Time Series modes:

```php
use \Javaabu\Stats\Enums\TimeSeriesModes;

$results = $stat->results(TimeSeriesModes::DAY); // returns a collection
```

Note that when calling the `results` method, the returned collection will not have any data for missing days (or hours, weeks, etc. depending on the mode you're using) within the given date range.

# Formatting the results

To have the missing days also included as `0` values, you can format your results using a formatter that fills the missing days:

```php
$formatted_results = $stat->format(
    'combined', // which format to use
    TimeSeriesModes::DAY); // returns an array
```

# Filtering the results

Some stats will also allow you to filter the results using certain allowed filter values. For example, the `UserLoginsRepository` allows filtering by a specific user. To filter the results, you can provide an array of filters when you instantiate the stat.

```php
use \Javaabu\Stats\TimeSeriesStats;
use \Javaabu\Stats\Enums\PresetDateRanges;

$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS, ['user' => 2]);
$filtered_results = $stat->results(TimeSeriesModes::DAY);
```

# Getting the total for the date range

You can get the total value for your given date range by calling the `total()` method of the stat.

```php
$total = $stat->total();
```


