---
title: Formatters
sidebar_position: 6
---

A formatter turns a stat's results into an array for a consumer: a JavaScript chart library, a CSV file, an API client. The [chart builders](../basic-usage/charts.md) don't use them; the [JSON API](./json-api.md) (`format=`), CSV exports and your own code do.

```php
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\TimeSeriesStats;

$stat = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS);
$compare = TimeSeriesStats::createFromMetric('user_logins', PresetDateRanges::LAST_7_DAYS->getPreviousDateRange());

$stat->format('chartjs', TimeSeriesModes::DAY);                                 // without a comparison

TimeSeriesStats::createFromFormat('chartjs')->format(TimeSeriesModes::DAY, $stat, $compare);
```

# Time series formats

`format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array`. The examples are for 28 June to 4 July 2024, compared with 21 to 27 June, with 5 logins on 3 July and 2 on 4 July.

## default

The results as they are: only steps with data. No gaps filled.

```php
[
    'stats' => [
        ['logins' => 5, 'day' => '2024-07-03'],
        ['logins' => 2, 'day' => '2024-07-04'],
    ],
    'compare' => [],        // null without a comparison
]
```

## chartjs

For [Chart.js](https://www.chartjs.org): labels, and a value per step. Labels of the two periods are joined with `#`. Gaps are filled with `0`.

```php
[
    'labels' => ['28 Jun 24#21 Jun 24', '29 Jun 24#22 Jun 24', '30 Jun 24#23 Jun 24', '1 Jul 24#24 Jun 24', '2 Jul 24#25 Jun 24', '3 Jul 24#26 Jun 24', '4 Jul 24#27 Jun 24'],
    'stats' => [0, 0, 0, 0, 0, 5, 2],
    'compare' => [0, 0, 0, 0, 0, 0, 0],     // null without a comparison
]
```

The labels use `stats.date_formats`.

## sparkline

A value per step, gaps filled. Ignores the comparison.

```php
[0, 0, 0, 0, 0, 5, 2]
```

## flot

For [Flot](https://www.flotcharts.org): `[index, value]` per step, gaps filled. Ignores the comparison.

```php
[[0, 0], [1, 0], [2, 0], [3, 0], [4, 0], [5, 5], [6, 2]]
```

## combined

A row per step, gaps filled. With a comparison, each step's row is followed by the comparison period's row, and both have the period's dates. The CSV export uses it.

```php
[
    ['logins' => 0, 'day' => '2024-06-28', 'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59'],
    ['logins' => 0, 'day' => '2024-06-21', 'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59'],
    // ...
    ['logins' => 2, 'day' => '2024-07-04', 'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59'],
    ['logins' => 0, 'day' => '2024-06-27', 'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59'],
]
```

Without a comparison, the rows have no `date_range`.

# Categorical formats

`format(CategoricalStatsRepository $stats, ?CategoricalStatsRepository $compare = null, CategoricalModes $mode = CategoricalModes::NON_EMPTY, array $categorical_values = []): array`. The categories follow the mode, in the provider's order, with `0` for categories without data.

```php
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;

$stat = CategoricalStats::createFromMetric('payments_by_island', PresetDateRanges::THIS_MONTH);
$compare = CategoricalStats::createFromMetric('payments_by_island', PresetDateRanges::LAST_MONTH);

$stat->format('combined', $compare, CategoricalModes::ALL);
CategoricalStats::createFromFormat('chartjs')->format($stat, $compare, CategoricalModes::SPECIFIC, [12, 15]);
```

## default

```php
[
    'stats' => [
        ['island' => 12, 'island_name' => 'Male', 'payments' => 40],
        ['island' => 15, 'island_name' => 'Hulhumale', 'payments' => 7],
    ],
    'compare' => [
        ['island' => 12, 'island_name' => 'Male', 'payments' => 31],
        ['island' => 15, 'island_name' => 'Hulhumale', 'payments' => 0],
    ],                      // null without a comparison
]
```

The keys are the stat's `getCategoryFieldAlias()`, `getCategoryNameFieldAlias()` and `getAggregateFieldName()`.

## combined

A row per category, with the comparison's value in `compare_{aggregate field}`. The CSV export uses it.

```php
[
    ['island' => 12, 'island_name' => 'Male', 'payments' => 40, 'compare_payments' => 31],
    ['island' => 15, 'island_name' => 'Hulhumale', 'payments' => 7, 'compare_payments' => 0],
]
```

`compare_payments` is `null` without a comparison.

## chartjs

```php
[
    'labels' => ['Male', 'Hulhumale'],
    'stats' => [40, 7],
    'compare' => [31, 0],   // null without a comparison
]
```

# Your own formatter

Extend `AbstractTimeSeriesStatsFormatter` (or `AbstractCategoricalStatsFormatter`) and implement `format()`:

```php
<?php

namespace App\Stats\Formatters;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\AbstractTimeSeriesStatsFormatter;
use Javaabu\Stats\Support\TimeSeriesSteps;

class ApexChartsFormatter extends AbstractTimeSeriesStatsFormatter
{
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $series = [[
            'name' => $stats->getName(),
            'data' => array_map(fn (array $step) => ['x' => $step['key'], 'y' => $step['value']], TimeSeriesSteps::for($stats, $mode)),
        ]];

        if ($compare) {
            $series[] = [
                'name' => $compare->formattedDateRange('YYYY-MM-DD'),
                'data' => array_map(fn (array $step) => ['x' => $step['key'], 'y' => $step['value']], TimeSeriesSteps::for($compare, $mode)),
            ];
        }

        return ['series' => $series];
    }
}
```

`TimeSeriesSteps::for($stat, $mode)` walks the range one step at a time and gives each step's `date` (a `CarbonInterface`), `key` (as in results) and `value` (`0` without data).

Register it in a service provider's `boot()`:

```php
use App\Stats\Formatters\ApexChartsFormatter;
use Javaabu\Stats\TimeSeriesStats;

TimeSeriesStats::registerFormatters([
    'apexcharts' => ApexChartsFormatter::class,
]);
```

It can now be used by name: `$stat->format('apexcharts')`, and `format=apexcharts` in the JSON API. Register categorical formatters with `CategoricalStats::registerFormatters()`. Pass `false` as the second argument to replace the registered formatters instead of adding to them.

| Method | On `TimeSeriesStats` and `CategoricalStats` |
| --- | --- |
| `registerFormatters(array $formatters, bool $merge = true)` | Register formatters by name |
| `formattersMap()` | Classes keyed by name |
| `allowedFormats()` | The names |
| `createFromFormat(string $format)` | A formatter instance |
| `getClassNameForFormat(string $name)`, `getNameForFormatter(string $class)` | Look up either way |
