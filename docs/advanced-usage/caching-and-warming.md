---
title: Caching and warming
sidebar_position: 1
---

Stats are aggregate queries over whole tables, so a dashboard of ten charts can be slow. The package caches what [charts](../basic-usage/charts.md) compute, clears it when the data changes, and can compute it ahead of time.

# What is cached

| | Cached |
| --- | --- |
| Charts (`TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline`, `StatTile`) | Yes, by default |
| The [stats pages](../basic-usage/stats-pages.md) | No, unless `stats.cache.pages` is `true` |
| The [JSON API](./json-api.md), and stats used directly (`$stat->total()`, `$stat->results()`) | No |

A chart's data is cached under a key made from everything that changes it: the chart type, its stats and their labels and filters, the chart's filters, the dates (presets by day), the mode, the options, the locale, and a version per metric. Two charts built the same way share one entry.

# How long

| Chart's period | Kept for | Config |
| --- | --- | --- |
| Ended before today | 1 day | `stats.cache.ttl.past` (seconds) |
| Includes today | 10 minutes | `stats.cache.ttl.current` (seconds) |

A chart can set its own time, or turn caching off:

```php
TimeSeriesChart::make('payments_count')->cache(300);          // 5 minutes
TimeSeriesChart::make('payments_count')->cache(minutes: 30);
TimeSeriesChart::make('payments_count')->cache(false);        // or ->withoutCache(), or ->cache(0)
```

Turn it off everywhere with `STATS_CACHE=false`, for example in `phpunit.xml`. Use another store than the default with `STATS_CACHE_STORE=redis`. Any store works; tags aren't needed.

# Clear it when data changes

```php
use Javaabu\Stats\Facades\Stats;

Stats::forget('payments_count');                          // charts that use this metric
Stats::forget(['payments_count', 'payments_by_island']);
Stats::forget();                                          // every chart, the same as Stats::flush()
Stats::flush();
```

Clearing bumps a version stored in the cache, so the old entries are no longer read; they expire on their own.

## From the model

Add `InvalidatesStats` to the models stats are computed from. Saving, deleting or restoring one clears the stats:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Stats\Concerns\InvalidatesStats;

class Payment extends Model
{
    use InvalidatesStats;

    /**
     * The metrics this model's changes affect. Without it, every metric.
     */
    protected array $invalidates_stats = ['payments_count', 'payment_amounts', 'payments_by_island'];
}
```

Override `statsToInvalidate(): array` to decide per model, for example by its status.

The trait listens to Eloquent events, so it doesn't see query builder updates and deletes (`Payment::query()->where(...)->update([...])`), imports, or writes by other apps. Call `Stats::forget()` after those.

On a busy table, every save clearing the stats means the cache rarely helps. Leave the trait off there and let the 10-minute TTL do the work, or warm the charts.

# Warm the cache

Warming computes charts ahead of time and puts them in the cache, so nobody waits for them. Register the charts once, in a service provider's `boot()`:

```php
use App\Stats\Dashboard;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Facades\Stats;

Stats::warm(fn () => Dashboard::all());

Stats::warm([
    StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare(),
]);
```

`warm()` takes a chart, a list of charts, or a closure that returns them. A closure is better: the charts are built when they're warmed, so relative ranges such as "this month" are current and the labels are translated then. The closure is called through the container, so it can type-hint dependencies.

Then warm them on a schedule:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;
use Javaabu\Stats\Jobs\WarmStatsCache;

Schedule::command('stats:warm')->everyFiveMinutes();

// or on a queue
Schedule::job(new WarmStatsCache)->everyFiveMinutes();
```

| Command | What |
| --- | --- |
| `php artisan stats:warm` | Compute every registered chart now, and list them |
| `php artisan stats:warm --queue` | Dispatch the `WarmStatsCache` job instead |
| `php artisan stats:warm --flush` | Clear every cached stat first |

The `WarmStatsCache` job runs on `stats.cache.warm.connection` and `stats.cache.warm.queue` (the defaults when `null`). It is unique for 10 minutes, so a slow warm-up isn't queued twice.

Warming recomputes and stores every chart even when it's still cached, so warm a little more often than `ttl.current` and visitors always hit the cache.

## Make sure the page uses the warmed data

The page finds the warmed entry only when it builds the same chart: the same stats, labels, filters, range, mode and options, in the same locale. Build the charts in one place and use it from both the controller and `Stats::warm()`, as in [Dashboards](../basic-usage/dashboards.md#a-complete-dashboard).

The command runs in the app's default locale. For a site in Dhivehi and English, warm both:

```php
// routes/console.php
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schedule;
use Javaabu\Stats\Facades\Stats;

Schedule::call(function () {
    foreach (['dv', 'en'] as $locale) {
        App::setLocale($locale);
        Stats::warmNow();
    }
})->everyFiveMinutes();
```

## From code

| Method | What |
| --- | --- |
| `Stats::warm(Chart\|iterable\|Closure $charts)` | Register charts to warm |
| `Stats::warmables()` | The registered charts, with closures resolved |
| `Stats::warmNow(?Closure $after = null)` | Compute and cache them now; returns how many. `$after` is called with each chart |
| `Stats::clearWarmables()` | Forget the registered charts (for tests) |
| `$chart->refresh()` | Compute one chart and cache it |
| `$chart->isWarm()` | Whether a chart's data is cached |

# The cache service

`Stats::cache()` returns the `Javaabu\Stats\Cache\StatsCache` singleton, for caching your own stat results the same way:

```php
use Javaabu\Stats\Facades\Stats;

$top_islands = Stats::cache()->remember(
    ['report' => 'top_islands', 'month' => now()->format('Y-m')],   // what the value depends on
    ['payments_by_island'],                                          // the metrics, for Stats::forget()
    fn () => $this->topIslands(),
    ttl: null,                                                       // null: by the end date
    ends_at: now()->endOfMonth(),
);
```

| Method | What |
| --- | --- |
| `remember(array $key, array $metrics, Closure $callback, ?int $ttl = null, ?CarbonInterface $ends_at = null)` | Get it, or compute and store it |
| `refresh(...)` | Compute and store it, whatever is cached. Same arguments |
| `has(array $key, array $metrics)` | Whether it's cached |
| `forget(string\|array $metrics = '*')`, `flush()` | Clear |
| `ttlFor(?CarbonInterface $ends_at = null)` | `ttl.past` for a period that ended before today, else `ttl.current` |
| `key(array $key, array $metrics)` | The cache key |
| `version(string $metric)` | A metric's current version |
| `enabled()`, `store()`, `prefix()` | From `stats.cache` |
