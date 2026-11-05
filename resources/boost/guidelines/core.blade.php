{{--
    javaabu/stats guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/stats

- This project builds stats with `javaabu/stats`. Define a stat as a class (`CountStatsRepository`, `SumStatsRepository`, `CountCategoricalStatsRepository`, `SumCategoricalStatsRepository`), register it with `TimeSeriesStats::register()` / `CategoricalStats::register()` (or `config('stats.time_series_stats')`), and show it with the chart builders (`TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline`, `StatTile`), drawn as SVG by `<x-stats::chart>` in Blade and by `<StatsChart>` from `@javaabu/stats` in React. Don't write raw `GROUP BY DATE(...)` queries, Chart.js code, or your own dashboard caching.
- Always activate the `javaabu-stats` skill when adding or changing a stat, a chart, a dashboard or stat tile, the stats pages or their routes, stats filters, stats caching, or tests of any of these.
