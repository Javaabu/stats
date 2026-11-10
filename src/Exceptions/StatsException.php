<?php

namespace Javaabu\Stats\Exceptions;

use RuntimeException;

class StatsException extends RuntimeException
{
    public static function unsupportedDriver(string $driver): self
    {
        return new self("Time series stats don't support the [{$driver}] database driver. Use MySQL, MariaDB, PostgreSQL or SQLite, or override getDateSqlForTimeMode() in your stat.");
    }

    public static function unknownMetric(string $metric): self
    {
        return new self("The stat [{$metric}] isn't registered.");
    }

    public static function reservedFilterName(string $name): self
    {
        return new self("A stats filter can't be named [{$name}]: the stats form uses that parameter.");
    }

    public static function inertiaMissing(): self
    {
        return new self('Install inertiajs/inertia-laravel to use the inertia views, or set stats.views to blade.');
    }
}
