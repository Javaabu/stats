---
title: Artisan commands
sidebar_position: 8
---

| Command | What |
| --- | --- |
| `stats:time-series` | Generate a time series stat and register it |
| `stats:categorical` | Generate a categorical stat and register it |
| `stats:warm` | Compute the charts registered with `Stats::warm()` and cache them |

# stats:time-series

```bash
php artisan stats:time-series {name} {model} [--type=count|sum] [--path=] [--force]
```

| Argument or option | What |
| --- | --- |
| `name` | The class name: `PaymentsCount` |
| `model` | The model: a class (`App\Models\Payment`), a name in `App\Models` (`Payment`), or a morph alias (`payment`) |
| `--type`, `-t` | `count` (the default) or `sum` |
| `--path`, `-p` | Where to put the file. Default: `app/Stats/TimeSeries` |
| `--force`, `-f` | Overwrite the file if it exists |

```bash
php artisan stats:time-series PaymentsCount Payment
php artisan stats:time-series PaymentAmounts Payment --type=sum
```

It writes `app/Stats/TimeSeries/PaymentsCount.php`, a `CountStatsRepository` (or `SumStatsRepository`) with the model's query and table, an empty `allowedFilters()`, and the aggregate field `count` (or `total`). For a sum, fill in `getFieldToSum()`.

Then it registers the stat in `app/Providers/AppServiceProvider.php`, with the class name in snake case as the metric (`payments_count`). For that to work, the provider must already have a `TimeSeriesStats::register([` call to add to:

```php
use Javaabu\Stats\TimeSeriesStats;

public function boot(): void
{
    TimeSeriesStats::register([
    ]);
}
```

The generated class uses `created_at`. Add `getDateFieldName()` for another date column, and `getName()` for a better name.

# stats:categorical

```bash
php artisan stats:categorical {name} {model} {category_model} {category_id_field} [--type=count|sum] [--path=] [--force]
```

| Argument or option | What |
| --- | --- |
| `name` | The class name: `PaymentsByIsland` |
| `model` | The model the stat counts or sums: a class, a name in `App\Models`, or a morph alias |
| `category_model` | The model whose records are the categories |
| `category_id_field` | The column on `model` that holds the category's id: `island_id`. A bare column is qualified with the table (`payments.island_id`); pass a qualified one for a join or alias |
| `--type`, `-t` | `count` (the default) or `sum` |
| `--path`, `-p` | Where to put the file. Default: `app/Stats/Categorical` |
| `--force`, `-f` | Overwrite the file if it exists |

```bash
php artisan stats:categorical PaymentsByIsland Payment Island island_id
php artisan stats:categorical PaymentAmountsByIsland Payment Island island_id --type=sum
```

It writes a `CountCategoricalStatsRepository` (or `SumCategoricalStatsRepository`) with the query, `categoryProvider()` returning the category model, the category field, the date field (`{table}.created_at`) and the aggregate field. `getCategoryFieldAlias()` is the category model's morph alias when it has one in the morph map, else `category`. For a sum, fill in `getFieldToSum()`.

It registers the stat in an existing `CategoricalStats::register([` call in `AppServiceProvider`, checks that it worked, and otherwise prints the lines to add yourself.

A `--type` other than `count` or `sum` fails with an error.

## Your own stubs

```bash
php artisan vendor:publish --tag=stats-stubs
```

The generators use the files in `stubs/vendor/stats` when they exist:

| Stub | Used for |
| --- | --- |
| `Stats/TimeSeries/CountStat.stub` | `stats:time-series` |
| `Stats/TimeSeries/SumStat.stub` | `stats:time-series --type=sum` |
| `Stats/Categorical/CountStat.stub` | `stats:categorical` |
| `Stats/Categorical/SumStat.stub` | `stats:categorical --type=sum` |

# stats:warm

```bash
php artisan stats:warm [--queue] [--flush]
```

| Option | What |
| --- | --- |
| `--queue` | Dispatch the `WarmStatsCache` job instead of warming now |
| `--flush` | Clear every cached stat first |

Without `--queue`, it lists each chart it warmed (its title and type) and the count. Schedule it:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('stats:warm')->everyFiveMinutes();
```

See [Caching and warming](./caching-and-warming.md).
