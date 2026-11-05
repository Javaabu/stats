---
title: Databases
sidebar_position: 7
---

Time series stats group their date column into hours, days, weeks, months or years with SQL. The package writes that SQL for each database it supports, so the same stat gives the same results on all of them:

| Driver | Database | Tested with |
| --- | --- | --- |
| `mysql` | MySQL | 8.4 |
| `mariadb` | MariaDB | 11 |
| `pgsql` | PostgreSQL | 17 |
| `sqlite` | SQLite | The version PHP ships |

The driver is read from the connection of the stat's query, so stats on different connections work side by side.

Categorical stats use a plain `GROUP BY` on the category field, and work on any database Laravel supports.

# Step keys

Every database returns the same key for a step:

| Mode | Key | Example |
| --- | --- | --- |
| `hour` | `YYYY-MM-DD HH:00` | `2024-07-04 09:00` |
| `day` | `YYYY-MM-DD` | `2024-07-04` |
| `week` | The date the week starts on, `YYYY-MM-DD` | `2024-06-30` |
| `month` | `YYYY-MM` | `2024-07` |
| `year` | `YYYY` | `2024` |

Weeks are keyed by their first day rather than a week number, so they don't depend on a database's week-numbering rules. The first day is Sunday, or Monday with `'week_starts_on_sunday' => false`. Change it before any data is cached, or clear the cache after (`php artisan stats:warm --flush`, or `Stats::flush()`).

Dates are grouped and compared as they are stored; the package doesn't convert time zones. Store dates in the app's time zone (`config('app.timezone')`), which is what Carbon uses for the date ranges.

# Other databases

Any other driver (`sqlsrv`, say) throws `Javaabu\Stats\Exceptions\StatsException` when a time series stat is grouped:

> Time series stats don't support the [sqlsrv] database driver. Use MySQL, MariaDB, PostgreSQL or SQLite, or override getDateSqlForTimeMode() in your stat.

Override `getDateSqlForTimeMode()` to give the SQL yourself. It must return the step's key in the formats above:

```php
<?php

namespace App\Stats\TimeSeries;

use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

abstract class SqlServerCountStatsRepository extends CountStatsRepository
{
    public function getDateSqlForTimeMode(TimeSeriesModes $mode): string
    {
        $date = $this->getDateField();

        return match ($mode) {
            TimeSeriesModes::HOUR => "FORMAT({$date}, 'yyyy-MM-dd HH:00')",
            TimeSeriesModes::DAY => "FORMAT({$date}, 'yyyy-MM-dd')",
            TimeSeriesModes::WEEK => "FORMAT(DATEADD(day, -((DATEPART(weekday, {$date}) + @@DATEFIRST - 1) % 7), {$date}), 'yyyy-MM-dd')",
            TimeSeriesModes::MONTH => "FORMAT({$date}, 'yyyy-MM')",
            TimeSeriesModes::YEAR => "FORMAT({$date}, 'yyyy')",
        };
    }
}
```

The week expression above starts weeks on Sunday; adjust it to match `week_starts_on_sunday`. It is an example: test it on your database, and compare it with the package's own expressions in `TimeSeriesModes::getSql()`.

`TimeSeriesModes::getSql(string $date_field, string $driver = 'mysql')` gives the package's SQL for any date expression and supported driver.
