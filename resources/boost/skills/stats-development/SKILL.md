---
name: stats-development
description: Use when creating stat classes, registering metrics, adding filters, setting up stats routes, or refactoring existing stats code with javaabu/stats.
---

# Stats Development

## When to use this skill

Use when creating stat repositories, registering metrics, adding filters, or setting up stats routes with `javaabu/stats`.

## Core Principle: Use What Exists

The package provides built-in controllers, routes, middleware, formatters, and export. Your job is to generate stat repository classes for each model — not to reimplement infrastructure. Specifically:

- **Use the Artisan generator** to scaffold stat classes — it auto-registers them too
- **Use `registerApiRoute()` / `registerRoutes()`** — never write custom route handlers for stats
- **Use built-in formatters** (`default`, `chartjs`, `sparkline`, `flot`, `combined`) before creating custom ones
- **Use the `stats.view-time-series` middleware** — it's auto-registered, don't recreate auth logic
- **Use `ExportsTimeSeriesStats` trait** in existing controllers for CSV export — don't build export from scratch

When refactoring existing stats code, check for: direct filter class instantiation (should use `StatsFilter` factory), manual route definitions (should use `registerApiRoute`/`registerRoutes`), and reimplemented formatting or export logic.

## Creating a Stat

Place stat classes in `app/Stats/TimeSeries/`. Extend `CountStatsRepository` for row counts or `SumStatsRepository` for numeric sums. One class per model/metric — each stat targets a single table.

**Quick path — Artisan generator (auto-registers in AppServiceProvider):**

```bash
php artisan stats:time-series OrdersCount Order --type=count
php artisan stats:time-series PaymentAmounts Payment --type=sum
```

**Manual — Count stat with filters** (namespace `App\Stats\TimeSeries`, import `StatsFilter`, `CountStatsRepository`, `Builder`):

```php
class OrdersCount extends CountStatsRepository
{
    public function query(): Builder { return Order::query(); }
    public function getTable(): string { return 'orders'; }
    public function getAggregateFieldName(): string { return 'count'; }

    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('customer', 'customer_id'),
            StatsFilter::exact('status', 'status'),
        ];
    }
}
```

**For a sum stat**, extend `SumStatsRepository` instead and add one extra method:

```php
public function getFieldToSum(): string
{
    return 'amount';
}
```

## Registering Stats

Register in `AppServiceProvider::boot()`. Metric names must be snake_case.

```php
use Javaabu\Stats\TimeSeriesStats;

public function boot(): void
{
    TimeSeriesStats::register([
        'orders_count' => OrdersCount::class,
        'payment_amounts' => PaymentAmounts::class,
    ]);
}
```

To suppress built-in user_signups/user_logins stats: `TimeSeriesStats::excludeDefaultStats();`

## Filters

Always use the `StatsFilter` factory. Never instantiate filter classes directly.

```php
use Javaabu\Stats\Filters\StatsFilter;

public function allowedFilters(): array
{
    return [
        // Exact column match
        StatsFilter::exact('customer', 'customer_id'),

        // Eloquent query scope — calls $query->whereActive()
        StatsFilter::scope('active', 'whereActive'),

        // Custom closure — receives ($query, $value, $stat)
        StatsFilter::closure('min_amount', function (Builder $query, $value, $stat) {
            return $query->where('amount', '>=', $value);
        }),
    ];
}
```

Pass filters when creating a stat instance:

```php
$stats = TimeSeriesStats::createFromMetric('orders_count', PresetDateRanges::THIS_YEAR, [
    'customer' => 5,
    'status' => 'completed',
]);
```

## Routes

**API route (JSON) — register in `routes/api.php`:**

```php
use Javaabu\Stats\TimeSeriesStats;

// IMPORTANT: Do NOT include /api in the URL — routes/api.php adds it automatically.
TimeSeriesStats::registerApiRoute('/stats/time-series', 'stats.time-series.index');
```

**Admin routes (web view + CSV export):**

```php
TimeSeriesStats::registerRoutes('/stats/time-series', 'stats.index', 'stats.export', ['auth', 'stats.view-time-series']);
```

The `stats.view-time-series` middleware alias is auto-registered by the package.

## Quick Reference

| What | How |
|------|-----|
| Base classes | `CountStatsRepository`, `SumStatsRepository` |
| Register metrics | `TimeSeriesStats::register(['name' => Class::class])` |
| Create instance | `TimeSeriesStats::createFromMetric('name', $dateRange, $filters)` |
| Time modes | `TimeSeriesModes::HOUR\|DAY\|WEEK\|MONTH\|YEAR` |
| Date ranges | `PresetDateRanges::THIS_YEAR\|LAST_30_DAYS\|LAST_7_DAYS\|...` |
| Custom range | `new ExactDateRange('2024-01-01', '2024-12-31')` |
| Format output | `$stat->format('chartjs', TimeSeriesModes::DAY)` |
| Built-in formats | `default`, `chartjs`, `sparkline`, `flot`, `combined` |
| Get total | `$stat->total()` |
| Custom date col | Override `getDateFieldName()` (default: `created_at`) |
| Authorization | Override `canView(?Authorizable $user)` (default: `view_stats` permission) |

See `references/` for formatters, export, authorization, and advanced features.

## Verify Against Live State

If the app has `laravel/mcp` installed, use the MCP tools to cross-check before writing code:

- **ListMetrics** — confirm which metrics are registered, their filters, and aggregate fields
- **ListFormatters** — confirm available formatters (including custom ones)
- **QueryStat** — test a metric with real data before building on top of it

This avoids guessing metric names or filter keys — the MCP tools reflect the actual running application.
