---
title: Dashboards
sidebar_position: 6
---

A dashboard is a page of [charts](./charts.md): a row of stat tiles, a chart or two, maybe sparklines in a table. Build the charts in PHP, draw them in Blade or React, and keep them in the cache so the page loads fast.

# Stat tiles

A `StatTile` shows a time series stat's total for a range, the change from the previous period and an optional sparkline:

```php
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;

$tiles = [
    StatTile::make('payments_count')->label('ފައިސާ ދެއްކުން')->range(PresetDateRanges::THIS_MONTH)->compare()->sparkline(TimeSeriesModes::DAY)->icon('credit-card'),
    StatTile::make('payment_amounts')->label('ޖުމްލަ ފައިސާ')->range(PresetDateRanges::THIS_MONTH)->compare()->valueFormat(Chart::FORMAT_CURRENCY)->icon('banknote'),
    StatTile::make('refunds_count')->label('ރީފަންޑު')->range(PresetDateRanges::THIS_MONTH)->compare()->positiveIsGood(false)->icon('rotate-ccw'),
];
```

**Blade**: `<x-stats::stat-tiles>` lays them out in a grid of up to `columns` per row (2, 3 or 4; default 4) that falls back to fewer columns in narrow containers:

```blade
<x-stats::stat-tiles :columns="3">
    @foreach($tiles as $tile)
        <x-stats::stat-tile :tile="$tile" />
    @endforeach
</x-stats::stat-tiles>
```

**React**: Onu's `StatTiles` does the same:

```tsx
import { StatTiles } from "@javaabu/onu/charts"
import { StatsTile, type StatTileData } from "@javaabu/stats"

<StatTiles>
  {tiles.map((tile) => (
    <StatsTile key={tile.id} tile={tile} />
  ))}
</StatTiles>
```

Tiles take time series stats. For a categorical figure on a dashboard, use a `CategoryChart` or `PieChart`. Every tile option is in [Charts](./charts.md#stattile).

# Sparklines

A `Sparkline` is a stat's trend without axes, for a table row or a card:

```php
use Javaabu\Stats\Charts\Sparkline;

$rows = Island::query()->orderBy('name')->get()->map(fn (Island $island) => [
    'island' => $island,
    'trend' => Sparkline::make('payments_count', $island->name)
        ->filters(['island' => $island->id])
        ->range(PresetDateRanges::LAST_30_DAYS)
        ->mode(TimeSeriesModes::DAY)
        ->area(),
]);
```

```blade
@foreach($rows as $row)
    <tr>
        <td>{{ $row['island']->name }}</td>
        <td><x-stats::sparkline :chart="$row['trend']" /></td>
    </tr>
@endforeach
```

```tsx
<td>
  <StatsChart chart={row.trend} />
</td>
```

Each sparkline runs its own query (and is cached on its own). Keep tables of sparklines short, or warm them.

# A complete dashboard

Put the dashboard's charts in one class, so the controller and the cache warmer build exactly the same charts:

```php
<?php

namespace App\Stats;

use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Charts\PieChart;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;

class Dashboard
{
    /**
     * @return list<StatTile>
     */
    public static function tiles(): array
    {
        return [
            StatTile::make('payments_count')
                ->label('ފައިސާ ދެއްކުން')
                ->range(PresetDateRanges::THIS_MONTH)
                ->compare()
                ->sparkline(TimeSeriesModes::DAY)
                ->icon('credit-card'),
            StatTile::make('payment_amounts')
                ->label('ޖުމްލަ ފައިސާ')
                ->range(PresetDateRanges::THIS_MONTH)
                ->compare()
                ->valueFormat(Chart::FORMAT_CURRENCY)
                ->icon('banknote'),
            StatTile::make('user_signups')
                ->label('އައު މެމްބަރުން')
                ->range(PresetDateRanges::THIS_MONTH)
                ->compare()
                ->icon('users'),
            StatTile::make('refunds_count')
                ->label('ރީފަންޑު')
                ->range(PresetDateRanges::THIS_MONTH)
                ->compare()
                ->positiveIsGood(false)
                ->icon('rotate-ccw'),
        ];
    }

    /**
     * @return array<string, Chart>
     */
    public static function charts(): array
    {
        return [
            'payments' => TimeSeriesChart::make('payments_count')
                ->title('ފައިސާ ދެއްކުން')
                ->description('ފާއިތުވި 30 ދުވަސް')
                ->range(PresetDateRanges::LAST_30_DAYS)
                ->mode(TimeSeriesModes::DAY)
                ->compare()
                ->area(),
            'by_island' => CategoryChart::make('payments_by_island')
                ->title('ރަށްތަކުން ފައިސާ ދެއްކުން')
                ->range(PresetDateRanges::THIS_MONTH)
                ->horizontal()
                ->top(10),
            'by_status' => PieChart::make('payments_by_status')
                ->title('ހާލަތު')
                ->range(PresetDateRanges::THIS_MONTH)
                ->donut(),
        ];
    }

    /**
     * Every chart, for warming the cache
     *
     * @return list<Chart>
     */
    public static function all(): array
    {
        return [...static::tiles(), ...array_values(static::charts())];
    }
}
```

## The controller

Charts don't check permissions, so the controller does:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Stats\Dashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->can('view_stats'), 403);

        return view('admin.dashboard', [
            'tiles' => Dashboard::tiles(),
            'charts' => Dashboard::charts(),
        ]);
    }
}
```

When stats have permissions of their own (a `canView()` override), keep only the charts the user may see:

```php
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\TimeSeriesStats;

$allowed = TimeSeriesStats::allowedMetrics([], $request->user());

$tiles = array_values(array_filter(
    Dashboard::tiles(),
    fn (Chart $tile) => array_diff($tile->metrics(), $allowed) === [],
));
```

Use `CategoricalStats::allowedMetrics()` for categorical charts.

## Blade

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-stats::stat-tiles :columns="4">
        @foreach($tiles as $tile)
            <x-stats::stat-tile :tile="$tile" />
        @endforeach
    </x-stats::stat-tiles>

    <x-stats::chart :chart="$charts['payments']" />

    <div class="grid gap-6 md:grid-cols-2">
        <x-stats::chart :chart="$charts['by_island']" />
        <x-stats::chart :chart="$charts['by_status']" />
    </div>
@endsection
```

## Inertia React

```php
use App\Stats\Dashboard;
use Inertia\Inertia;
use Inertia\Response;
use Javaabu\Stats\Charts\Chart;

public function __invoke(Request $request): Response
{
    abort_unless($request->user()->can('view_stats'), 403);

    return Inertia::render('Admin/Dashboard', [
        'tiles' => array_map(fn (Chart $tile) => $tile->toArray(), Dashboard::tiles()),
        'charts' => array_map(fn (Chart $chart) => $chart->toArray(), Dashboard::charts()),
    ]);
}
```

```tsx
// resources/js/pages/Admin/Dashboard.tsx
import { StatTiles } from "@javaabu/onu/charts"
import { StatsChart, StatsTile, type StatsChartData, type StatTileData } from "@javaabu/stats"

interface DashboardProps {
  tiles: StatTileData[]
  charts: {
    payments: StatsChartData
    by_island: StatsChartData
    by_status: StatsChartData
  }
}

export default function Dashboard({ tiles, charts }: DashboardProps) {
  return (
    <div className="flex flex-col gap-6">
      <StatTiles>
        {tiles.map((tile) => (
          <StatsTile key={tile.id} tile={tile} />
        ))}
      </StatTiles>

      <StatsChart chart={charts.payments} />

      <div className="grid gap-6 md:grid-cols-2">
        <StatsChart chart={charts.by_island} />
        <StatsChart chart={charts.by_status} />
      </div>
    </div>
  )
}
```

# Keep it fast

Every chart's data is cached: for 10 minutes when its period includes today, for a day when it doesn't. The first visitor after that pays for the queries. To take that away, warm the cache ahead of time:

```php
// AppServiceProvider::boot()
use App\Stats\Dashboard;
use Javaabu\Stats\Facades\Stats;

Stats::warm(fn () => Dashboard::all());
```

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('stats:warm')->everyFiveMinutes();
```

Warming only helps when the page builds the same charts: the cache key covers the stats, filters, dates, options (including a tile's label, icon and link) and the locale. Build them in one place, as above.

And clear a stat's charts when its data changes, with the `InvalidatesStats` trait on the model or `Stats::forget('payments_count')`. See [Caching and warming](../advanced-usage/caching-and-warming.md).
