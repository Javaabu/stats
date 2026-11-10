---
title: Charts
sidebar_position: 5
---

A chart is described in PHP, from registered stats, and drawn either in Blade (as SVG, on the server) or in React (with [Onu](https://github.com/Javaabu/onu)'s chart components). The same object feeds both.

| Builder | Draws | Stats |
| --- | --- | --- |
| `TimeSeriesChart` | Lines, areas or bars over time | Time series |
| `CategoryChart` | Bars (vertical or horizontal), lines or areas over categories | Categorical |
| `PieChart` | A pie or donut of each category's share | Categorical |
| `Sparkline` | A tiny line or area with no axes | Time series |
| `StatTile` | A KPI tile: the total, the change from the previous period, a sparkline | Time series |

All are in `Javaabu\Stats\Charts`. Make one with `make(?string $metric = null, ?string $label = null)`, or through the `Stats` facade:

```php
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Facades\Stats;

$chart = TimeSeriesChart::make('payments_count');
$chart = Stats::timeSeries('payments_count');    // the same

Stats::category('payments_by_island');
Stats::pie('payments_by_status');
Stats::sparkline('payments_count');
Stats::tile('payments_count');
```

# Draw a chart

Build it in the controller and hand it to the view or the page:

```php
use Illuminate\Contracts\View\View;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;

public function index(): View
{
    $payments = TimeSeriesChart::make('payments_count')
        ->title('ފައިސާ ދެއްކުން')
        ->range(PresetDateRanges::LAST_30_DAYS)
        ->compare()
        ->area();

    return view('payments.stats', ['payments' => $payments]);
}
```

**Blade**

```blade
<x-stats::chart :chart="$payments" />

{{-- or echo it --}}
{{ $payments }}
```

**Inertia React**

```php
use Inertia\Inertia;
use Inertia\Response;

public function index(): Response
{
    return Inertia::render('Payments/Stats', [
        'payments' => TimeSeriesChart::make('payments_count')->title('ފައިސާ ދެއްކުން')->range(PresetDateRanges::LAST_30_DAYS)->compare()->area()->toArray(),
    ]);
}
```

```tsx
// resources/js/pages/Payments/Stats.tsx
import { StatsChart, type TimeSeriesChartData } from "@javaabu/stats"

export default function Stats({ payments }: { payments: TimeSeriesChartData }) {
  return <StatsChart chart={payments} />
}
```

The data is computed when the chart is turned into an array (`toArray()`, `json_encode()`, rendering), and cached; see [Caching](#caching). A chart of a metric that isn't registered throws `Javaabu\Stats\Exceptions\StatsException` at that point.

Charts don't check permissions. Check them in the controller, as for anything else on the page: `abort_unless($request->user()->can('view_stats'), 403)`, or a stat's own `canView()`.

## Blade components

| Component | Takes |
| --- | --- |
| `<x-stats::chart :chart="$chart" />` | Any chart, or its array |
| `<x-stats::time-series-chart :chart="$chart" />` | The same as `chart` |
| `<x-stats::category-chart :chart="$chart" />` | The same as `chart` |
| `<x-stats::pie-chart :chart="$chart" />` | The same as `chart` |
| `<x-stats::sparkline :chart="$chart" />` | The same as `chart` |
| `<x-stats::stat-tile :tile="$tile" />` | A `StatTile` (or `:chart="$tile"`) |
| `<x-stats::stat-tiles :columns="4">…</x-stats::stat-tiles>` | A responsive grid of tiles. `columns`: up to 2, 3 or 4 per row (default 4) |

Every chart component also takes `:rtl="true|false"`. By default charts run right to left when the app's locale is in `stats.rtl_locales`. Other attributes (`class`, `id`) go on the chart's root element.

A Blade chart is a `<figure>` with a title, the SVG, a legend when there is more than one series, an accessible summary, and a "View as table" disclosure with the numbers. With a comparison, the totals of both periods and the change are shown above it. Series take their colours from `--chart-1` to `--chart-8` in order; set `--color-{series key}` (for example `--color-payments_count`) to give one series its own colour.

## React components

Import them from `@javaabu/stats` (javaabu/manifest resolves the alias; run `php artisan manifest:types` for TypeScript):

| Export | What |
| --- | --- |
| `StatsChart` | Props: `chart` (any chart's array) and `className`. Picks Onu's `TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline` or `StatTile` by the chart's type |
| `StatsTile` | Props: `tile` (a `StatTile`'s array), plus any of Onu's `StatTile` props except `label` and `value` (`loading`, `tip`, `chart`, `className`). With `href`, the whole tile is a link |
| Types | `StatsChartData` (the union), `TimeSeriesChartData`, `CategoryChartData`, `PieChartData`, `SparklineData`, `StatTileData`, `StatsValueFormat`, `StatsComparison`, `StatsSeriesConfig` |

Lay tiles out with Onu's `StatTiles`:

```tsx
import { StatTiles } from "@javaabu/onu/charts"
import { StatsTile, type StatTileData } from "@javaabu/stats"

export function Tiles({ tiles }: { tiles: StatTileData[] }) {
  return (
    <StatTiles>
      {tiles.map((tile) => (
        <StatsTile key={tile.id} tile={tile} />
      ))}
    </StatTiles>
  )
}
```

# Options of every chart

| Method | Default | What |
| --- | --- | --- |
| `stat(string $metric, ?string $label = null, array $filters = [])` | | Add a stat, with its own label and filters |
| `stats(array $metrics)` | | Add several: `['payments_count', 'refunds_count']`, or labels keyed by metric |
| `title(?string $title)` | The first stat's name | The heading |
| `description(?string $description)` | None | A line under the heading |
| `id(?string $id)` | A hash of the chart | The HTML `id` |
| `range(DateRange\|string $range)` | `stats.default_date_range` | A `PresetDateRanges` case or value (`'last_30_days'`), or an `ExactDateRange` |
| `filters(array $filters)` | `[]` | Filters for every stat of the chart |
| `valueFormat(string $format)` | `number` | How values are shown. See [Value formats](#value-formats) |
| `height(?int $height)` | Onu's, or 300 in Blade (32 for a Blade sparkline) | Height in pixels |
| `cache(int\|bool $seconds = true, ?int $minutes = null)` | On, with the default TTLs | Cache for that long: `cache(300)`, `cache(minutes: 5)`. `cache(false)` or `cache(0)` turns it off |
| `withoutCache()` | | The same as `cache(false)` |

And to read it:

| Method | What |
| --- | --- |
| `toArray()`, `jsonSerialize()` | The chart as the props of `<StatsChart>` and the Blade components |
| `data()` | The computed part only, from the cache when it's there |
| `refresh()` | Compute again and put it in the cache |
| `isWarm()` | Whether the data is in the cache |
| `render()`, `toHtml()` | The Blade component's HTML |
| `type()` | `time-series`, `category`, `pie`, `sparkline` or `stat-tile` |
| `metrics()` | The metrics it uses |
| `getTitle()`, `getId()`, `getRange()`, `getFilters()`, `getValueFormat()`, `getHeight()`, `isCached()`, `getSeries()` | The options |

# TimeSeriesChart

```php
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;

TimeSeriesChart::make('payments_count')
    ->title('ފައިސާ ދެއްކުން')
    ->range(PresetDateRanges::LAST_30_DAYS)
    ->mode(TimeSeriesModes::DAY)
    ->line();
```

| Method | Default | What |
| --- | --- | --- |
| `mode(TimeSeriesModes\|string $mode)` | `stats.default_time_series_mode` | Group by `hour`, `day`, `week`, `month` or `year` |
| `variant(string $variant)`, `line()`, `area()`, `bar()` | `line` | How series are drawn. Constants: `TimeSeriesChart::LINE`, `AREA`, `BAR` |
| `stacked(bool $stacked = true)` | `false` | Stack the series (areas or bars). Ignored with a comparison |
| `brush(bool $brush = true)` | `false` | A range slider to zoom (React only) |
| `compare(DateRange\|string\|bool $with = true)` | `false` | Compare the first stat with the previous period, or with the period given |
| `positiveIsGood(bool $positive_is_good = true)` | `true` | Whether a rise is good (green) in the comparison. `false` for refunds, complaints, waiting times |
| `periods(array $periods)` | | Plot the first stat over each period |
| `getMode()`, `getCompareRange()` | | |

Every step of the range is there, with `0` where there is no data.

## Several stats on one plot

```php
TimeSeriesChart::make()
    ->stats([
        'payments_count' => 'ފައިސާ ދެއްކުން',
        'refunds_count' => 'ރީފަންޑު',
    ])
    ->range(PresetDateRanges::THIS_MONTH)
    ->bar()
    ->stacked();
```

The same stat can be plotted twice with different filters:

```php
TimeSeriesChart::make()
    ->stat('payments_count', 'މާލެ', ['island' => 12])
    ->stat('payments_count', 'ހުޅުމާލެ', ['island' => 15])
    ->range(PresetDateRanges::LAST_30_DAYS);
```

Each series gets a key from its metric (`payments_count`, then `payments_count_2`), used in the data rows and in `--color-{key}`.

## Compare with the previous period

```php
TimeSeriesChart::make('payments_count')
    ->range(PresetDateRanges::THIS_MONTH)
    ->compare();                                        // last month, the same length

TimeSeriesChart::make('payments_count')
    ->range(PresetDateRanges::THIS_MONTH)
    ->compare(PresetDateRanges::THIS_MONTH->getPreviousDateRange());

TimeSeriesChart::make('complaints_count')
    ->range(new ExactDateRange('2024-07-01', '2024-07-31 23:59:59'))
    ->compare(new ExactDateRange('2023-07-01', '2023-07-31 23:59:59'))
    ->positiveIsGood(false);
```

The previous period is drawn muted (dashed, for lines), step for step against the current one, and the chart shows both totals and the change. The series are labelled with the periods: a preset's label ("This month"), or the dates. Only the first stat is compared.

## Several periods

Plot one stat over several periods, each period a series, step by step on the first period's dates:

```php
TimeSeriesChart::make('payments_count')
    ->mode(TimeSeriesModes::MONTH)
    ->periods([PresetDateRanges::THIS_YEAR, PresetDateRanges::LAST_YEAR])
    ->title('މިއަހަރާއި ފާއިތުވި އަހަރު');
```

The series are keyed `period_1`, `period_2`, … and labelled with the periods. With `periods()`, only the first stat is used, and `range()` and `compare()` are ignored.

## The data

`toArray()` gives the props of Onu's `TimeSeriesChart`:

```php
[
    'type' => 'time-series',
    'id' => 'stats-chart-1a2b3c4d',
    'title' => 'ފައިސާ ދެއްކުން',
    'description' => null,
    'valueFormat' => 'number',
    'height' => null,
    'data' => [
        ['date' => '2024-07-03', 'payments_count' => 3, 'previous' => 0],
        ['date' => '2024-07-04', 'payments_count' => 2, 'previous' => 1],
    ],
    'labels' => ['3 Jul 24', '4 Jul 24'],          // stats.date_formats, in the app's locale
    'config' => ['payments_count' => ['label' => 'This month'], 'previous' => ['label' => '1 Jun 2024 – 30 Jun 2024']],
    'totals' => ['payments_count' => 5, 'previous' => 1],
    'dateKey' => 'date',
    'mode' => 'day',
    'interval' => 'day',                              // null for years
    'variant' => 'line',
    'stacked' => false,
    'brush' => false,
    'comparison' => ['previousKey' => 'previous', 'currentLabel' => 'This month', 'previousLabel' => '1 Jun 2024 – 30 Jun 2024', 'positiveIsGood' => true],
    'aggregateLabel' => 'Payments',
]
```

# CategoryChart

```php
use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Enums\CategoricalModes;

CategoryChart::make('payments_by_island')
    ->title('ރަށްތަކުން ފައިސާ ދެއްކުން')
    ->range(PresetDateRanges::THIS_YEAR)
    ->horizontal()
    ->top(10);
```

| Method | Default | What |
| --- | --- | --- |
| `variant(string $variant)`, `bar()`, `line()`, `area()` | `bar` | Constants: `CategoryChart::BAR`, `LINE`, `AREA` |
| `horizontal(bool $horizontal = true)` | `false` | Bars along the side, for long category names. Bars only |
| `stacked(bool $stacked = true)` | `false` | Stack the series. Ignored with a comparison |
| `compare(DateRange\|string\|bool $with = true)` | `false` | Compare the first stat with the previous period, or the period given |
| `positiveIsGood(bool $positive_is_good = true)` | `true` | |
| `categoryMode(CategoricalModes\|string $mode)` | `stats.default_categorical_mode` | `non_empty`, `all` or `specific`. See [Categorical stats](./categorical-stats.md#which-categories) |
| `categories(array $ids)` | | Only these categories (sets the mode to `specific`) |
| `top(?int $limit)` | | Only the categories with the highest values of the first stat, highest first |
| `getCategoryMode()`, `getCompareRange()` | | |

Several categorical stats side by side, with the categories of the first:

```php
CategoryChart::make()
    ->stats(['payments_by_island' => 'ފައިސާ', 'refunds_by_island' => 'ރީފަންޑު'])
    ->range(PresetDateRanges::THIS_YEAR);
```

Each row of `data` is a category: `['category' => 'Male', 'categoryId' => 12, 'payments_by_island' => 40, 'previous' => 31]`. The array also has `config`, `categoryKey`, `categoryLabel` (the provider's title), `aggregateLabel`, `variant`, `orientation` (`null` unless bars), `stacked` and `comparison`.

# PieChart

```php
use Javaabu\Stats\Charts\PieChart;

PieChart::make('payments_by_status')
    ->range(PresetDateRanges::THIS_MONTH)
    ->donut()
    ->centerLabel('MVR 4.2M', 'ބަޖެޓް');
```

| Method | Default | What |
| --- | --- | --- |
| `donut(bool $donut = true)` | `false` | A ring instead of a pie |
| `centerLabel(string\|false\|null $value, ?string $label = null)` | The total | The donut's centre. `false` for none |
| `keepEmpty(bool $keep = true)` | `false` | Keep categories without a value as empty slices |
| `top(?int $limit)` | | The largest slices, and the rest added up as one "Other" slice |
| `categoryMode()`, `categories()` | | As for `CategoryChart` |

Slices are largest first. Only the first stat is drawn. `data` is a list of `['name' => 'Paid', 'value' => 40, 'categoryId' => 'paid']` (the "Other" slice has `categoryId` `null`); the array also has `nameKey`, `valueKey`, `variant` (`pie` or `donut`), `centerLabel` (`null` for the total, `false` for none), `nameLabel`, `aggregateLabel` and `total`.

# Sparkline

```php
use Javaabu\Stats\Charts\Sparkline;

Sparkline::make('payments_count', 'ފައިސާ ދެއްކުން')
    ->range(PresetDateRanges::LAST_30_DAYS)
    ->mode(TimeSeriesModes::DAY)
    ->area()
    ->color('var(--chart-3)');
```

| Method | Default | What |
| --- | --- | --- |
| `mode(TimeSeriesModes\|string $mode)` | `stats.default_time_series_mode` | One value per step |
| `area(bool $area = true)` | A line | Fill under the line |
| `color(?string $color)` | `var(--chart-1)` | Any CSS colour or token |

`data` is a list of values, one per step. `label` (the stat's label, or the title) names the sparkline for screen readers.

# StatTile

```php
use Javaabu\Stats\Charts\StatTile;

StatTile::make('payments_count')
    ->label('ފައިސާ ދެއްކުން')
    ->range(PresetDateRanges::THIS_MONTH)
    ->compare()
    ->sparkline(TimeSeriesModes::DAY)
    ->icon('credit-card')
    ->href(route('payments.index'));
```

| Method | Default | What |
| --- | --- | --- |
| `label(?string $label)` | The stat's label, or its name | The tile's label (the same as `title()`) |
| `compare(DateRange\|string\|bool $with = true)` | `false` | Show the change from the previous period, or the period given |
| `deltaContext(?string $context)` | "vs previous period" | The text after the change |
| `positiveIsGood(bool $positive_is_good = true)` | `true` | Whether a rise is good (green) |
| `sparkline(TimeSeriesModes\|string\|bool $mode = true)` | None | A sparkline of the range, by the mode given or the default mode |
| `icon(?string $icon)` | None | An Onu semantic icon name: `credit-card`, `file-text`, `users` |
| `href(?string $href)` | None | Make the whole tile a link |
| `footer(?string $footer)` | None | A line at the bottom |

The value is the stat's `total()` for the range. When there is no change to show (no comparison, or nothing in the previous period), the Blade tile shows the period under the value instead.

The array: `label`, `value`, `previousValue`, `deltaContext`, `positiveIsGood`, `sparkline` (a list of values or `null`), `icon`, `href`, `footer` and `period`.

# Value formats

`valueFormat()` takes the same names as Onu's charts. Constants are on `Javaabu\Stats\Charts\Chart`.

| Format | Constant | Shows |
| --- | --- | --- |
| `number` | `Chart::FORMAT_NUMBER` | `12,345.6` (axis ticks are compact: `12.3K`) |
| `integer` | `Chart::FORMAT_INTEGER` | `12,346` |
| `compact` | `Chart::FORMAT_COMPACT` | `12.3K` |
| `percent` | `Chart::FORMAT_PERCENT` | `0.256` as `25.6%` |
| `currency` | `Chart::FORMAT_CURRENCY` | `MVR 1,250.00`, or `ރ. 1,250.00` in Dhivehi |

```php
StatTile::make('payment_amounts')->valueFormat(Chart::FORMAT_CURRENCY);
```

Numbers use Latin digits in Dhivehi, as Onu shows them. See [Dhivehi and RTL](../advanced-usage/dhivehi-and-rtl.md).

# Caching

Chart data is cached by default, keyed by everything that changes it (the stats, filters, dates, mode, options and locale). Data for a period that ended before today keeps for a day; data that includes today keeps for 10 minutes. Clear it when the data changes:

```php
Stats::forget('payments_count');      // charts that use the metric
Stats::flush();                       // every chart
```

or add the `InvalidatesStats` trait to the model. See [Caching and warming](../advanced-usage/caching-and-warming.md).
