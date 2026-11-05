---
title: Introduction
sidebar_position: 1.0
---

# Stats

[javaabu/stats](https://github.com/Javaabu/stats) turns your Eloquent data into statistics: how many payments came in each day, how much was paid per island, how this month compares with the last one.

- **Defined once.** A stat is a small class: a query, a date column, and what to count or sum. Register it under a metric name (`payments_count`) and use it everywhere.
- **Two kinds of stats.** Time series stats group a value by hour, day, week, month or year. Categorical stats group it by a category: a status, an island, a customer.
- **Charts in PHP.** `TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline` and `StatTile` describe a chart in PHP. Blade draws it as SVG with no JavaScript; React draws it with [Onu](https://github.com/Javaabu/onu)'s chart components. Plot several stats together, several periods, or compare with the previous period.
- **Stats pages for free.** `Route::stats()` adds a time series page and a categorical page: a form to pick the stat, the dates and the filters, the chart, and a CSV download. In Blade or Inertia React.
- **Dashboards that stay fast.** Chart data is cached, invalidated when your models change, and can be computed ahead of time with `stats:warm`.
- **Filters.** Let a stat be filtered by a column, a scope, a closure or a [javaabu/filters](https://github.com/Javaabu/filters) filter, which also adds a field to the stats form.
- **Any SQL database.** MySQL, MariaDB, PostgreSQL and SQLite.
- **Dhivehi.** Every string is translated, the charts work right to left, and CSV files open with Thaana intact.

```php
namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCount extends CountStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->whereNotNull('paid_at');
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

```php
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;

$tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare()->sparkline();
$chart = TimeSeriesChart::make('payments_count')->range(PresetDateRanges::LAST_30_DAYS)->compare()->area();
```

```blade
<x-stats::stat-tile :tile="$tile" />
<x-stats::chart :chart="$chart" />
```

```tsx
<StatsTile tile={tile} />
<StatsChart chart={chart} />
```

Start with [Installation & Setup](./installation-and-setup.md), then [Time series stats](./basic-usage/time-series-stats.md) and [Charts](./basic-usage/charts.md). Coming from v1? Read [Upgrading](./upgrading.md).
