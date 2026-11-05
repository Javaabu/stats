# Stats

[![Tests](https://github.com/n4ff4h/stats/actions/workflows/tests.yml/badge.svg?branch=next-gen)](https://github.com/n4ff4h/stats/actions/workflows/tests.yml?query=branch%3Anext-gen)
[![codecov](https://codecov.io/gh/n4ff4h/stats/branch/next-gen/graph/badge.svg)](https://codecov.io/gh/n4ff4h/stats/tree/next-gen)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/stats.svg)](https://packagist.org/packages/javaabu/stats)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Time series and categorical stats for Laravel. Define a stat once, then chart it in Blade or Inertia React with [Onu](https://github.com/Javaabu/onu)'s charts.

- Count, sum or any aggregate of a model per hour, day, week, month or year, or per category, on MySQL, MariaDB, PostgreSQL or SQLite.
- Line, area, bar, pie and donut charts, sparklines and KPI tiles: SVG in Blade, Onu's components in React, the same tokens in both.
- Several stats or several periods on one plot, and comparisons with the previous period.
- Cached dashboards with sensible TTLs, background warming and invalidation.
- Stats pages with forms defined in [javaabu/stencil](https://github.com/Javaabu/stencil), filters from [javaabu/filters](https://github.com/Javaabu/filters), a JSON API and CSV exports.
- A policy and the `view_stats` permission through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard.
- Dhivehi and right-to-left.

```php
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\TimeSeriesStats;

TimeSeriesStats::register(['payments_count' => PaymentsCount::class]);

$tile = StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare()->sparkline();
$chart = TimeSeriesChart::make('payments_count')->range(PresetDateRanges::LAST_30_DAYS)->compare()->area();
```

```blade
<x-stats::stat-tile :tile="$tile" />
<x-stats::chart :chart="$chart" />
```

```tsx
import { StatsChart } from "@javaabu/stats"

<StatsChart chart={chart} />
```

```php
Route::stats(['prefix' => 'admin/stats', 'name' => 'admin.stats', 'guard' => 'web_admin']);
```

Coming from v1? Your stat classes keep working; see [Upgrading](docs/upgrading.md).

## Documentation

You'll find the documentation on [https://docs.javaabu.com/stats](https://docs.javaabu.com/stats), and in [`docs/`](docs/intro.md).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving this package? Feel free to create an [issue](../../issues) on GitHub, we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Testing

```bash
composer test     # PHP, on SQLite; DB_CONNECTION=mysql|mariadb|pgsql for another database
pnpm check        # React: lint, format, typecheck, Vitest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [Arushad Ahmed (@dash8x)](http://arushad.com)
- [Athfan Khaleel (@athphane)](https://github.com/athphane)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
