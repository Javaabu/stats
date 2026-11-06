<?php

namespace Javaabu\Stats\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Javaabu\Stats\Enums\Concerns\HasLabels;
use Javaabu\Stats\Exceptions\StatsException;
use Javaabu\Stats\TimeSeriesStats;

/**
 * How a time series stat is grouped: one value per hour, day, week, month or year
 */
enum TimeSeriesModes: string
{
    use HasLabels;

    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::HOUR => __('Hour'),
            self::DAY => __('Day'),
            self::WEEK => __('Week'),
            self::MONTH => __('Month'),
            self::YEAR => __('Year'),
        };
    }

    public function diffMethodName(): string
    {
        return Str::camel('diff_in_' . Str::plural($this->value));
    }

    public function incrementMethodName(): string
    {
        return Str::camel('add_' . $this->value);
    }

    public function queryMethodName(): string
    {
        return Str::camel($this->value);
    }

    /**
     * The number of steps between the two dates, not counting the first
     */
    public function interval(CarbonInterface $date_from, CarbonInterface $date_to): int
    {
        $diff_method = $this->diffMethodName();

        return (int) $this->startOfStep($date_from)->{$diff_method}($this->startOfStep($date_to), true);
    }

    /**
     * The display format (moment.js / Carbon isoFormat), from `config('stats.date_formats')`
     */
    public function getDateFormat(): string
    {
        return config('stats.date_formats.' . $this->value);
    }

    /**
     * The isoFormat of the keys the database groups by
     */
    public function getInternalDateFormat(): string
    {
        return match ($this) {
            self::HOUR => 'YYYY-MM-DD HH:00',
            self::DAY, self::WEEK => 'YYYY-MM-DD',
            self::MONTH => 'YYYY-MM',
            self::YEAR => 'YYYY',
        };
    }

    /**
     * The SQL that turns a date column into the step's key. Weeks are keyed
     * by the date they start on, so every database groups them the same way.
     *
     * @throws StatsException for a database driver that isn't supported
     */
    public function getSql(string $date_field, string $driver = 'mysql'): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => $this->mysqlSql($date_field),
            'pgsql' => $this->pgsqlSql($date_field),
            'sqlite' => $this->sqliteSql($date_field),
            default => throw StatsException::unsupportedDriver($driver),
        };
    }

    protected function mysqlSql(string $date_field): string
    {
        $week_start = TimeSeriesStats::firstDayOfWeek();

        return match ($this) {
            self::HOUR => "DATE_FORMAT({$date_field}, '%Y-%m-%d %H:00')",
            self::DAY => "DATE_FORMAT({$date_field}, '%Y-%m-%d')",
            self::WEEK => "DATE_FORMAT(DATE_SUB({$date_field}, INTERVAL MOD(DAYOFWEEK({$date_field}) - 1 - {$week_start} + 7, 7) DAY), '%Y-%m-%d')",
            self::MONTH => "DATE_FORMAT({$date_field}, '%Y-%m')",
            self::YEAR => "DATE_FORMAT({$date_field}, '%Y')",
        };
    }

    protected function pgsqlSql(string $date_field): string
    {
        $week_start = TimeSeriesStats::firstDayOfWeek();

        return match ($this) {
            self::HOUR => "to_char({$date_field}, 'YYYY-MM-DD HH24:00')",
            self::DAY => "to_char({$date_field}, 'YYYY-MM-DD')",
            self::WEEK => "to_char(CAST({$date_field} AS date) - ((CAST(EXTRACT(DOW FROM {$date_field}) AS integer) - {$week_start} + 7) % 7), 'YYYY-MM-DD')",
            self::MONTH => "to_char({$date_field}, 'YYYY-MM')",
            self::YEAR => "to_char({$date_field}, 'YYYY')",
        };
    }

    protected function sqliteSql(string $date_field): string
    {
        $week_start = TimeSeriesStats::firstDayOfWeek();

        return match ($this) {
            self::HOUR => "strftime('%Y-%m-%d %H:00', {$date_field})",
            self::DAY => "strftime('%Y-%m-%d', {$date_field})",
            self::WEEK => "date({$date_field}, '-' || ((CAST(strftime('%w', {$date_field}) AS INTEGER) - {$week_start} + 7) % 7) || ' days')",
            self::MONTH => "strftime('%Y-%m', {$date_field})",
            self::YEAR => "strftime('%Y', {$date_field})",
        };
    }

    /**
     * The start of the step the date is in
     */
    public function startOfStep(CarbonInterface $date): CarbonInterface
    {
        $date = $date->copy();

        return match ($this) {
            self::HOUR => $date->startOfHour(),
            self::DAY => $date->startOfDay(),
            self::WEEK => $date->startOfWeek(TimeSeriesStats::firstDayOfWeek())->startOfDay(),
            self::MONTH => $date->startOfMonth(),
            self::YEAR => $date->startOfYear(),
        };
    }

    public function increment(CarbonInterface $date): CarbonInterface
    {
        $date = $date->copy()->locale(TimeSeriesStats::dateLocale());

        $method = $this->incrementMethodName();

        return $date->{$method}();
    }

    /**
     * Format a date for display, or as the key the database groups by
     */
    public function formatDate(CarbonInterface $date, bool $for_display = true): string
    {
        $date = $date->copy()->locale(TimeSeriesStats::dateLocale());

        if ($for_display) {
            return $date->isoFormat($this->getDateFormat());
        }

        if ($this === self::WEEK) {
            $date = $this->startOfStep($date);
        }

        return $date->isoFormat($this->getInternalDateFormat());
    }

    /**
     * A step's label in the app's locale (Dhivehi month names in `dv`), for
     * charts. Weeks read "Week 29, 2024", numbered by `stats.date_locale`.
     */
    public function displayDate(CarbonInterface $date, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($this === self::WEEK) {
            $week = $date->copy()->locale(TimeSeriesStats::dateLocale());

            return __('Week :week, :year', ['week' => $week->isoFormat('w'), 'year' => $week->isoFormat('gggg')]);
        }

        return $date->copy()->locale($locale)->isoFormat($this->getDateFormat());
    }

    /**
     * The step as an ISO 8601 date, for charts with a time axis
     */
    public function toIsoDate(CarbonInterface $date): string
    {
        $date = $this->startOfStep($date);

        return $this === self::HOUR
            ? $date->format('Y-m-d\TH:i:s')
            : $date->format('Y-m-d');
    }

    /**
     * The interval name Onu's TimeSeriesChart understands, if any
     */
    public function chartInterval(): ?string
    {
        return $this === self::YEAR ? null : $this->value;
    }
}
