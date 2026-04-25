# Querying Stats & Formatting Output

## Querying Stats Programmatically

```php
use Javaabu\Stats\TimeSeriesStats;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;

// Create a stat instance
$stat = TimeSeriesStats::createFromMetric('orders_count', PresetDateRanges::LAST_30_DAYS, [
    'status' => 'completed',
]);

// Get results grouped by time mode
$results = $stat->results(TimeSeriesModes::DAY);  // Collection of [count, day]

// Get formatted output for a charting library
$chartData = $stat->format('chartjs', TimeSeriesModes::DAY);

// Get aggregate total
$total = $stat->total();
```

## Preset Date Ranges

`PresetDateRanges` enum values: `TODAY`, `YESTERDAY`, `THIS_WEEK`, `LAST_WEEK`, `THIS_MONTH`, `LAST_MONTH`, `THIS_YEAR`, `LAST_YEAR`, `LAST_7_DAYS`, `LAST_14_DAYS`, `LAST_30_DAYS`, `LAST_5_YEARS`, `LAST_10_YEARS`, `LIFETIME`.

For custom ranges:

```php
use Javaabu\Stats\Support\ExactDateRange;

$range = new ExactDateRange('2024-01-01', '2024-12-31');
$stat = TimeSeriesStats::createFromMetric('orders_count', $range);
```

## Comparison Periods

```php
$current = PresetDateRanges::THIS_MONTH;
$previous = $current->getPreviousDateRange();

$currentStats = TimeSeriesStats::createFromMetric('orders_count', $current);
$previousStats = TimeSeriesStats::createFromMetric('orders_count', $previous);
```

## Time Series Modes

`TimeSeriesModes` enum: `HOUR`, `DAY`, `WEEK`, `MONTH`, `YEAR`. Controls SQL grouping granularity.

```php
$stat->results(TimeSeriesModes::MONTH);  // Monthly aggregation
$stat->format('chartjs', TimeSeriesModes::WEEK);  // Weekly chart data
```

## Built-in Formatters

`default`, `chartjs`, `sparkline`, `flot`, `combined`. Request via API with `?format=chartjs`.

## Creating a Custom Formatter

Extend `AbstractTimeSeriesStatsFormatter` and implement `format()`:

```php
<?php

namespace App\Formatters;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\AbstractTimeSeriesStatsFormatter;

class ReactFormatter extends AbstractTimeSeriesStatsFormatter
{
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $field = $stats->getAggregateFieldName();
        $results = $stats->results($mode);

        return [
            'labels' => $results->pluck($mode->value)->toArray(),
            'values' => $results->pluck($field)->toArray(),
            'total' => $stats->total(),
        ];
    }
}
```

Register in a service provider:

```php
TimeSeriesStats::registerFormatters([
    'react' => \App\Formatters\ReactFormatter::class,
]);
```
