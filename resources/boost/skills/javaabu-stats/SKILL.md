---
name: javaabu-stats
description: Define, chart, cache and test stats in Laravel with javaabu/stats. Use whenever the project depends on javaabu/stats and the task adds or changes a time series or categorical stat (counts or sums of a model over time or by category), registers metrics, draws a chart, sparkline, pie or KPI/stat tile in Blade or Inertia React, builds a dashboard, compares periods or plots several stats together, adds stats filters (javaabu/filters), sets up Route::stats() / Route::statsApi() pages and the JSON API, exports stats to CSV, caches or warms dashboard stats, changes who may view stats, makes a stats page for one model, upgrades from stats v1, or tests stats.
license: MIT
metadata:
  author: Javaabu
---

# Stats with javaabu/stats

A stat is a class that knows its query, date column and aggregate. You register it under a metric name, then show it with a chart builder. The package groups dates on MySQL, MariaDB, PostgreSQL and SQLite, draws the charts with Onu's tokens (SVG in Blade, Onu's components in React), caches them, and gives you stats pages, a JSON API and CSV exports. The docs ship with the package: `vendor/javaabu/stats/llms.txt` lists every page in `vendor/javaabu/stats/docs/`.

| Task | Read |
| --- | --- |
| Define, register, chart and cache stats | This file |
| Every class, option and route | [references/api.md](references/api.md) |
| Mistakes to avoid, upgrading from v1 | [references/common-mistakes.md](references/common-mistakes.md) |

## 1. Define a stat

```bash
php artisan stats:time-series PaymentsCount Payment            # count
php artisan stats:time-series PaymentsSum Payment --type=sum
php artisan stats:categorical PaymentsByUser Payment User user_id    # model, category model, category id column
```

Or by hand:

```php
<?php

namespace App\Stats\TimeSeries;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\SelectFilter;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCount extends CountStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
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
        return __('Payments');
    }

    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('user', 'user_id'),                                              // fixed: filters[user]=1
            StatsFilter::from(SelectFilter::make('status')->options(PaymentStatus::class)),     // a field in the stats form: status=paid
        ];
    }
}
```

Sums extend `SumStatsRepository` and add `getFieldToSum()`. Categorical stats extend `CountCategoricalStatsRepository` / `SumCategoricalStatsRepository` and add `categoryProvider()` (a model class, an enum class, an array, a query or a `CategoryProvider`), `getCategoryField()` and `getDateField()`.

## 2. Register it

```php
// AppServiceProvider::boot()
TimeSeriesStats::register(['payments_count' => PaymentsCount::class]);
CategoricalStats::register(['payments_by_status' => PaymentsByStatus::class]);
```

Or in `config/stats.php`: `'time_series_stats' => [...]`, `'categorical_stats' => [...]`.

## 3. Chart it

```php
use Javaabu\Stats\Charts\{CategoryChart, PieChart, Sparkline, StatTile, TimeSeriesChart};
use Javaabu\Stats\Enums\{PresetDateRanges, TimeSeriesModes};

$tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare()->sparkline()->icon('credit-card');

$trend = TimeSeriesChart::make('payments_count')
    ->mode(TimeSeriesModes::DAY)
    ->range(PresetDateRanges::LAST_30_DAYS)
    ->compare()                                   // against the previous 30 days
    ->area();

$both = TimeSeriesChart::make()->stats(['payments_count', 'refunds_count'])->bar();          // several stats
$years = TimeSeriesChart::make('payments_count')->mode('month')
    ->periods([PresetDateRanges::THIS_YEAR, PresetDateRanges::LAST_YEAR]);                  // several periods

$by_status = PieChart::make('payments_by_status')->range(PresetDateRanges::THIS_YEAR)->donut()->top(5);
$by_island = CategoryChart::make('payments_by_island')->horizontal()->top(10);
```

Blade:

```blade
<x-stats::stat-tiles :columns="3">
    <x-stats::stat-tile :tile="$tile" />
</x-stats::stat-tiles>

<x-stats::chart :chart="$trend" />
```

Inertia React (pass the chart as a prop; it's `JsonSerializable`):

```tsx
import { StatsChart, StatsTile, type StatsChartData, type StatTileData } from "@javaabu/stats"

export default function Dashboard({ trend, tile }: { trend: StatsChartData; tile: StatTileData }) {
  return (
    <>
      <StatsTile tile={tile} />
      <StatsChart chart={trend} />
    </>
  )
}
```

`@javaabu/stats` comes from the Composer package through javaabu/manifest's Vite plugin; the app installs `@javaabu/onu` and `recharts` (`php artisan manifest:check` lists them in apps with `@inertiajs/react`; Blade apps need none of them).

## 4. Cache dashboards

Charts cache their data by default: `stats.cache.ttl.current` (10 minutes) for periods that include today, `stats.cache.ttl.past` (a day) for periods that ended. `->cache(minutes: 5)` or `->withoutCache()` per chart.

```php
// AppServiceProvider::boot(): charts to keep warm
Stats::warm(fn () => [
    StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare(),
    TimeSeriesChart::make('payments_count')->range(PresetDateRanges::LAST_30_DAYS),
]);

// routes/console.php
Schedule::job(new WarmStatsCache)->everyFiveMinutes();
```

Invalidate with `Stats::forget('payments_count')`, `Stats::flush()`, or the `InvalidatesStats` trait on the model (`protected array $invalidates_stats = ['payments_count'];`). Build the dashboard's charts the same way as the warmed ones so the cache keys match.

## 5. Stats pages, API and permissions

```php
// routes/web.php, in the admin group
Route::stats(['prefix' => 'admin/stats', 'name' => 'admin.stats', 'guard' => 'web_admin']);

// routes/api.php
Route::statsApi(['middleware' => ['auth:api']]);
```

The pages (time series and categorical) are GET forms defined with stencil; `config('stats.views')` picks `blade` or `inertia`. Viewing a stat needs `view_stats` (registered with javaabu/permissions; run `php artisan permissions:sync`). For one model's stats, extend `TimeSeriesStatsController` and return the fixed filters from `filters(Request $request)`.

## 6. Test

```php
#[Test]
public function it_shows_this_months_payments_on_the_dashboard(): void
{
    $this->travelTo('2024-07-04');
    Payment::factory()->count(3)->create(['paid_at' => '2024-07-03']);

    $tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->withoutCache()->toArray();

    $this->assertEquals(3, $tile['value']);
}
```

Use `->withoutCache()` (or `config()->set('stats.cache.enabled', false)`) in tests that change data after a chart is built, travel to a fixed date, and assert on `toArray()` or the rendered Blade.
