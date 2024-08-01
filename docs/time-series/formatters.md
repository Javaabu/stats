---
title: Formatters
sidebar_position: 5
---

Formatters allow formatting the results from your stats classes to different formats.

```php
$formatted_results = $stat->format('default', TimeSeriesModes::DAY);
```

# Instantiating a formatter

You can instantiate a formatter instance by calling the `TimeSeriesStats::createFromFormat` method with the formatter's registered name.

```php
use \Javaabu\Stats\TimeSeriesStats;

$formatter = TimeSeriesStats::createFromFormat('chartjs');
```

# Using a formatter

You can use a formatter outside a stat class, by calling the `format` method of the formatter.

```php
use \Javaabu\Stats\TimeSeriesStats;
use \Javaabu\Stats\Enums\PresetDateRanges;
use \Javaabu\Stats\Enums\TimeSeriesModes;

$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
$comparison = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS->getPreviousDateRange());

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat, $comparison);
```

The `format` method accepts a time series mode, a stat class object and another optional stat class object for a comparison period. Note that only certain formatters support comparison stats. 

# Available Formats

The package ships with several different formatters that can work with different JS graph libraries.

## default

This is the default formatter which returns an array of `stats` and `compare`. This formatter doesn't fill missing values. 

```php
$formatter = TimeSeriesStats::createFromFormat('default');
$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat);

// returns the following
/*
[
    'stats' => [
        [
            'logins' => 5,
            'day' => '2024-07-03',
        ],
        [
            'logins' => 2,
            'day' => '2024-07-04',
        ]
    ],

    'compare' => null
] 
 */
```

## chartjs

This formatter produces results compatible with the [`chart.js`](https://www.chartjs.org/) JS library. This formatter will fill missing days.

```php
$formatter = TimeSeriesStats::createFromFormat('chartjs');
$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
$compare = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS->->getPreviousDateRange());

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

// returns the following
/*
[
    'labels' => [
        '28 Jun 24#21 Jun 24',
        '29 Jun 24#22 Jun 24',
        '30 Jun 24#23 Jun 24',
        '1 Jul 24#24 Jun 24',
        '2 Jul 24#25 Jun 24',
        '3 Jul 24#26 Jun 24',
        '4 Jul 24#27 Jun 24',
    ],

    'stats' => [
        0,
        0,
        0,
        0,
        0,
        5,
        2,
    ],

    'compare' => [
        0,
        0,
        0,
        0,
        0,
        0,
        0,
    ]
] 
 */
```

## sparkline

This formatter produces results compatible with the [`jquery-sparkline`](https://omnipotent.net/jquery.sparkline) JS library. This formatter will fill missing days. This formatter doesn't support comparison data.

```php
$formatter = TimeSeriesStats::createFromFormat('sparkline');
$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat);

// returns the following
/*
[
    0,
    0,
    0,
    0,
    0,
    5,
    2,
] 
 */
```

## flot

This formatter produces results compatible with the [`flot`](https://www.flotcharts.org/) JS library. This formatter will fill missing days.

```php
$formatter = TimeSeriesStats::createFromFormat('flot');
$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
$compare = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS->->getPreviousDateRange());

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

// returns the following
/*
[
    [0, 0],
    [1, 0],
    [2, 0],
    [3, 0],
    [4, 0],
    [5, 5],
    [6, 2],
] 
 */
```

## combined

This formatter combines the main stat and the comparison data into a single array. This formatter will fill missing days.

```php
$formatter = TimeSeriesStats::createFromFormat('combined');
$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
$compare = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS->->getPreviousDateRange());

$formatted_results = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

// returns the following
/*
[
    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-06-28',
        'logins' => 0,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-21',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-06-29',
        'logins' => 0,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-22',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-06-30',
        'logins' => 0,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-23',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-07-01',
        'logins' => 0,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-24',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-07-02',
        'logins' => 0,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-25',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-07-03',
        'logins' => 5,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-26',
        'logins' => 0,
    ],

    [
        'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
        'day' => '2024-07-04',
        'logins' => 2,
    ],
    [
        'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
        'day' => '2024-06-27',
        'logins' => 0,
    ],
] 
 */
```

# Defining your own formatter

To define your own formatter, you should extend the `AbstractTimeSeriesStatsFormatter` class and implement the `format` method.

```php
<?php
/**
 * Default stats formatter
 */

namespace App\Stats\Formatters;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;

class CustomFormatter extends AbstractTimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $stats = $stats->results($mode);
        $compare = $compare?->results($mode);

        return compact('stats', 'compare');
    }
}

```

After creating your formatter class, you should register the formatter by calling the `TimeSeriesStats::registerFormatters()` method in your `AppServiceProvider`'s `boot` method. When registering the formatter, you should provide a unique name for each formatter.

```php
use \Javaabu\Stats\TimeSeriesStats;

class AppServiceProvider extends ServiceProvider
{   
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {       
        TimeSeriesStats::registerFormatters([           
            'custom_format' => \App\Stats\Formatters\CustomFormatter::class,          
        ]);
    }
}
```
